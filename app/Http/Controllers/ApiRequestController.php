<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Jobs\CreateHashFromAPK;
use App\Jobs\CreateHashFromAppName;
use App\Models\ApiRequest;

use App\Models\Application;
use App\Models\File;
use App\Models\Hash;
use App\Objects\CreateHash;
use App\Objects\CreateHashFromPcap;
use Illuminate\Support\Facades\Bus;

const REQUEST_TYPES = [
    'data_request', 'create_hash_APK', 'create_hash_PCAP',
    'create_hash_PNAME', 'insert_hashes', 'get_apps_by_hashes', 'get_apps_hashes'
];

class ApiRequestController extends Controller { 

    public function generateApiKey (Request $request) {

        $auth_api_key = Str::random(30);
        User::where('id','=',$request->user_id)->update([
            'api_auth_key' => $auth_api_key,
        ]);

        return response()->json(['auth_api_key' => $auth_api_key]);
    }

    public function getApiKey (Request $request) {
        $auth_api_key = User::select('api_auth_key')->where('id','=',$request->user_id)->first();
        return response()->json($auth_api_key);
    }

    public function getRequests () {

        $requests = DB::table('users')
            ->select(
                'api_requests.id AS id',
                'users.email AS email',
                'api_requests.type AS type',
                'api_requests.ip_address AS ip_address',
            )
            ->join('api_requests', 'users.id', '=', 'api_requests.user_id')
            ->get();

        return response()->json($requests, 200);
    }

    // External API

    public function getAppHashes (Request $request) {

        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[1]);
        $results = [];

        foreach (json_decode($request->input('apps')) as $app){
            $hashes =  DB::table('applications')
                ->select('hashes.hash', 'hashes.hash_type')
                ->join('hashes','hashes.app_id','=','applications.id');

            if($app->package_name != null)
                $hashes->where('applications.package_name','=',$app->package_name);
            if($app->version != null)
                $hashes->where('applications.version','=',$app->version);
            if($app->hash_types != null)
                $hashes->whereIn('hashes.hash_type',$app->hash_types);

            $results[$app->package_name] = $hashes->get();
        }

        return response()->json($results, 200);
    }

    public function getAppsFromHashes (Request $request) {

        $this->registerApiRequest($request->header('auth_key'), $request->ip(), REQUEST_TYPES[5]);
        $results = [];

        foreach (json_decode($request->input('hashes')) as $hash) {
           $apps = DB::table('applications')
           ->select(
            'applications.name',
            'applications.package_name',
            'applications.version',
            'applications.is_malware'
            )
           ->join('hashes', 'hashes.app_id', '=', 'applications.id')
           ->where('hashes.hash', '=', $hash)
           ->get();
           $results[$hash] = $apps;
        }

        return response()->json($results, 200);
    }

    public function createHashFromAPK (Request $request) {

        $this->registerApiRequest($request->header('auth_key'), $request->ip(), REQUEST_TYPES[6]);

        $validator = Validator::make($request->all(), [
            'hash_types' => 'required',
            'apk_file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $hash_types = json_decode($request->input('hash_types'));
        $apk_file_name = $this->saveApkFile($request->file('apk_file'));
        $process_id = 'ext_api_'.Str::random(20);

        $job_id = CreateHashFromAPK::dispatch(
            $apk_file_name, 
            $hash_types,
            $request->ip(),
            $process_id,
        )->onQueue('default');

        return response()->json('Task was added to queue succesfully.', 200);
    }

     /**
     * @param $file
     * @return string
     */
    private function saveApkFile($file): string {
        $file_name = $file->getClientOriginalName();
        $final_name = date('his') .'_'. $file_name;
        $file->storeAs('uploads/apk_inserted',$final_name,'public');

        return $final_name;
    }

    public function createHashFromPackageName (Request $request) {

        $this->registerApiRequest($request->header('auth_key'), $request->ip(), REQUEST_TYPES[3]);

        $validator = Validator::make($request->all(), [
            'package_name' => 'required|string',
            'hash_types' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $process_id = 'ext_api_'.Str::random(20);
        $hash_types = json_decode($request->input('hash_types'));

        $job = CreateHashFromAppName::dispatch(
            $request->input('package_name'),
            $hash_types,
            $request->ip(),
            $process_id,
        )->onQueue('default');

        //Bus::dispatchedSync($job);

        return response()->json('Task was added to queue succesfully.', 200);
    }

    public function createHashFromPcap (Request $request) {

        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[2]);

        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string',
            'package_name' => 'required|string',
            'app_version' => 'required|string',
            'hash_types' => 'required',
            'pcap_file' => 'required|file',
            'is_malware' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $app_data = [
            'app_name' => $request->input('app_name'),
            'package_name' => $request->input('package_name'),
            'app_version' => $request->input('app_version'),
            'is_malware' => $request->input('is_malware')
        ];

        $pcap_file_name = $this->savePcapFile($request->file('pcap_file'));
        $hash_types = json_decode($request->input('hash_types'));

        $pcap_hash = new CreateHashFromPcap($pcap_file_name, $hash_types, $app_data);
        $hashes = $pcap_hash->create();
        
        return response()->json($hashes, 200);
    }

    private function savePcapFile($file): string {

        $file_name = $file->getClientOriginalName();
        $final_name = date('his') .'_'. $file_name;
        $file->storeAs('uploads/pcap_inserted',$final_name,'public');

        return $final_name;
    }

    public function insertHashes (Request $request) {

        $this->registerApiRequest($request->header('auth_key'), $request->ip(), REQUEST_TYPES[4]);

        $application = [
            'name' => $request->app_name,
            'package_name' => $request->package_name,
            'version' => $request->app_version,
        ];

        $application = Application::firstOrCreate($application, $application);

        foreach($request->hashes as $hash){
            $new_hash = [
                'app_id' => $application->id,
                'hash' => $hash,
                'hash_type' => $request->hash_type,
                'is_malware' => $request->is_malware,
            ];
        }

        $identifier = [
            'app_id' => $application->id,
            'hash' => $request->hash,
            'hash_type' => $request->hash_type,
        ];

        $new_hash = [
            'app_id' => $application->id,
            'hash' => $request->hash,
            'hash_type' => $request->hash_type,
            'is_malware' => $request->is_malware,
        ];

        Hash::firstOrCreate($identifier, $new_hash);
    }

    private function registerApiRequest ($auth_key, $ip_address, $type) {

        $user = User::where('api_auth_key', '=', $auth_key)->first();

        $api_request = ApiRequest::create([
            'user_id' => $user->id,
            'ip_address' => $ip_address,
            'type' => $type,
        ]);

        //TODO: Description column of api request

        $api_request->save();
    }
}
