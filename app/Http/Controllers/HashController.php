<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Jobs\CreateHashFromAPK;
use App\Jobs\CreateHashFromAppName;
use App\Objects\CreateHashFromPcap;
use App\Models\Process as ProcessModel;
use PhpParser\Node\Stmt\TryCatch;
use App\Objects\CreateHash;

use App\Models\Application;
use App\Models\File;
use App\Models\Hash;
use Illuminate\Support\Facades\Storage;

const PCAP_PATH = 'app/public/uploads/pcap_inserted/';

class HashController extends Controller {

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createHashFromAPK(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'hash_types' => 'required',
            'frontend_id' => 'required|string',
            'apk_file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $ip_address = $request->ip();
        $frontend_id = $request->input('frontend_id');
        $hash_types = json_decode($request->input('hash_types'));
        $apk_file_name = $this->saveApkFile($request->file('apk_file'));

        $job_id = CreateHashFromAPK::dispatch(
            $apk_file_name,
            $hash_types,
            $ip_address,
            $frontend_id
        )->onQueue('default');

        return response()->json(['job_id' => $job_id], 200);
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


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createHashFromPcap(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'app_name_pcap' => 'required|string',
            'package_name_pcap' => 'required|string',
            'app_version_pcap' => 'required|string',
            'hash_types_pcap' => 'required',
            'pcap_file' => 'required|file',
            'is_malware_pcap' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $app_data = [
            'app_name' => $request->input('app_name_pcap'),
            'package_name' => $request->input('package_name_pcap'),
            'app_version' => $request->input('app_version_pcap'),
            'is_malware' => $request->input('is_malware_pcap')
        ];

        $pcap_file_name = $this->savePcapFile($request->file('pcap_file'));
        $hash_types = json_decode($request->input('hash_types_pcap'));

        $pcap_hash = new CreateHashFromPcap($pcap_file_name, $hash_types);
        $hashes = $pcap_hash->createAndSave($app_data);

        return response()->json(['hashes' => $hashes], 200);
    }

    /**
     * @param $file
     * @return string
     */
    private function savePcapFile($file): string {
        $file_name = $file->getClientOriginalName();
        $final_name = date('his') .'_'. $file_name;
        $file->storeAs('uploads/pcap_inserted',$final_name,'public');

        return $final_name;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createHashFromAppName(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'frontend_id' => 'required|string',
            'package_name' => 'required|string',
            'hash_types' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $ip_address = $request->ip();
        $frontend_id = $request->input('frontend_id');

        $job_id = CreateHashFromAppName::dispatch(
            $request->package_name, 
            $request->hash_types,
            $ip_address,
            $frontend_id
            )->onQueue('default');

        return response()->json(['job_id' => $job_id], 200);
    }

    public function createHashFromTextInput(Request $request): JsonResponse {
        $application = Application::create([
            'name' => $request->app_name,
            'package_name' => $request->package_name,
            'version' => $request->app_version,
        ]);

        $application->save();

        $identifier = [
            'app_id' => $application->id,
            'hash' => $request->hash,
            'hash_type' => $request->hash_type,
        ];

        $new_record = [
            'app_id' => $application->id,
            'hash' => $request->hash,
            'hash_type' => $request->hash_type,
            'is_malware' => $request->is_malware,
        ];

        Hash::firstOrCreate($identifier, $new_record);

        return response()->json('process success!', 200);
    }

    public function createHashFromTextFile(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'text_file' => 'required|file|mimes:txt',
            'hash_types' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $hash_types = json_decode($request->input('hash_types'));
        $ip_address = $request->ip();
        $text_file_path = $this->saveTextFile($request->file('text_file'));

        $package_names = file($text_file_path);

        $response = [];

        foreach($package_names as $package_name){
            $frontend_id = 'int_api_'.Str::random(20);
            $response[$package_name] = $frontend_id;

            CreateHashFromAppName::dispatch(
                $package_name, $hash_types, $ip_address, $frontend_id
            )->onQueue('default');
        }

        return response()->json($response, 200);
    }

    /**
     * @param $file
     * @return string
     */
    private function saveTextFile($file): string {
        $file_name = $file->getClientOriginalName();
        $final_name = date('his') .'_'. $file_name;

        $file->storeAs('uploads/text_inserted',$final_name,'public');
        $file_path = storage_path('app/public/uploads/text_inserted/').$final_name;

        return $file_path;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getProcessInfo(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'frontend_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $job_id = $request->input('frontend_id');
        
        $info = ProcessModel::select('status','progress','message')->where('job_id','=',$job_id)->first();

        return response()->json($info, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getProcessResults(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'frontend_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $frontend_id = $request->input('frontend_id');
        $JA3_hashes = $JA3S_hashes = $FlowMon_hashes = [];

        $data = DB::table('processes')
            ->select(
                'applications.name as app_name','applications.package_name as package_name',
                'applications.version as app_version','hashes.hash as hash','hashes.hash_type as hash_type'
            )
            ->join('hashes','processes.id','=','hashes.process_id')
            ->join('applications','applications.id','=','hashes.app_id')
            ->where('processes.job_id','=',$frontend_id)
            ->get();

        foreach ($data as $item){
            if ($item->hash_type == 'JA3')
                $JA3_hashes[] = $item->hash;
            else if ($item->hash_type == 'JA3S')
                $JA3S_hashes[] = $item->hash;
            else if ($item->hash_type == 'FlowMon')
                $FlowMon_hashes[] = $item->hash;
        }

        $results = [
            'app_name' => $data[0]->app_name,
            'package_name' => $data[0]->package_name,
            'app_version' => $data[0]->app_version,
            'JA3_hashes' => $JA3_hashes,
            'JA3S_hashes' => $JA3S_hashes,
            'FlowMon_hashes' => $FlowMon_hashes,
        ];

        return response()->json($results, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAppHashAPI(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'params' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $app_name = $request->input('params')['app_name'];
        $hash_types = $request->input('params')['hash_types'];

        //FIX: app hashes
        $response = (new DatabaseController)->getAppHashes($app_name, $hash_types);

        return response()->json($response, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getHashesForAdmin(): JsonResponse {
        // ["id", "hash", "hash_type", "app_name", "package_name", "version"];
        $data = DB::table('applications')
            ->join('hashes','applications.id','=','hashes.app_id')
            ->select('hashes.id', 'hash','hash_type', 'name AS app_name', 'package_name', 'version')
            ->get();

        return response()->json($data, 200);
    }
}
