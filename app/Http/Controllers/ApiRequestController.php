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
use App\Models\Hash;
use App\Objects\CreateHashFromPcap;

const REQUEST_TYPES = [
    "get_app_hashes", "get_apps_from_hashes", "create_hash_from_apk",
    "create_hash_from_package_name", "create_hash_from_pcap",
    "analyze_flowmon_file", "analyze_pcap_file",
];

class ApiRequestController extends Controller {

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function generateApiKey(Request $request): JsonResponse {

        $auth_api_key = Str::random(30);
        User::where('id','=',$request->user_id)->update([
            'api_auth_key' => $auth_api_key,
        ]);

        return response()->json(['auth_api_key' => $auth_api_key]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getApiKey(Request $request): JsonResponse {
        $auth_api_key = User::select('api_auth_key')->where('id','=',$request->user_id)->first();
        return response()->json($auth_api_key);
    }

    /**
     * @return JsonResponse
     */
    public function getRequests(): JsonResponse {

        $requests = DB::table('users')
            ->select(
                'api_requests.id AS id',
                'users.email AS email',
                'api_requests.type AS type',
                'api_requests.status AS status',
                'api_requests.ip_address AS ip_address',
            )
            ->join('api_requests', 'users.id', '=', 'api_requests.user_id')
            ->get();

        return response()->json($requests, 200);
    }

    /**
     * @return JsonResponse
     */
    public function getRequestsUser(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }


        $requests = DB::table('users')
            ->select(
                'api_requests.id AS id',
                'users.email AS email',
                'api_requests.type AS type',
                'api_requests.status AS status',
                'api_requests.ip_address AS ip_address',
            )
            ->join('api_requests', 'users.id', '=', 'api_requests.user_id')
            ->where('users.id', '=', $request->user_id)
            ->get();

        return response()->json($requests, 200);
    }


    // External API

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAppHashes(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'auth_key' => 'required|string',
            'apps' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[0]);
        $results = [];

        foreach (json_decode($request->input('apps')) as $app){
            $hashes =  DB::table('applications')
                ->select('hashes.ja3_hash', 'hashes.ja3s_hash', 'hashes.sni', 'hashes.ja4_hash', 'hashes.ja4s_hash')
                ->join('hashes','hashes.app_id','=','applications.id');

            if($app->package_name != null)
                $hashes->where('applications.package_name','=',$app->package_name);
            if($app->version != null)
                $hashes->where('applications.version','=',$app->version);

            $results[$app->package_name] = $hashes->get();
        }

        return response()->json($results, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAppsFromHashes(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'auth_key' => 'required|string',
            'data' => 'required',
            'input_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[1]);
        $results = [];

        $query = DB::table('applications')->select(
            'applications.name',
            'applications.package_name',
            'applications.version',
            'applications.is_malware',
            'applications.is_dangerous',
            'hashes.ja3_hash',
            'hashes.ja3s_hash',
            'hashes.ja4_hash',
            'hashes.ja4s_hash',
            'hashes.sni',
            )
            ->distinct()
            ->join('hashes', 'hashes.app_id', '=', 'applications.id');

        foreach (json_decode($request->input('data')) as $item){

            if($request->input("input_type") == "JA3"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item->ja3_hash);
                });
                $results[] = ["ja3_hash" => $item->ja3_hash, "apps" => []];
            }else if($request->input("input_type") == "JA4"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja4_hash', $item->ja4_hash);
                });
                $results[] = ["ja4_hash" => $item->ja4_hash, "apps" => []];
            }else if($request->input("input_type") == "JA3_JA3S"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item->ja3_hash);
                    $query->where('hashes.ja3s_hash', $item->ja3s_hash);
                });
                $results[] = ["ja3_hash" => $item->ja3_hash, "ja3s_hash" => $item->ja3s_hash, "apps" => []];
            }else if($request->input("input_type") == "JA4_JA4S"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja4_hash', $item->ja4_hash);
                    $query->where('hashes.ja4s_hash', $item->ja4s_hash);
                });
                $results[] = ["ja4_hash" => $item->ja4_hash, "ja4s_hash" => $item->ja4s_hash, "apps" => []];
            }else if($request->input("input_type") == "JA3_SNI"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item->ja3_hash);
                    $query->where('hashes.sni', $item->sni);
                });
                $results[] = ["ja3_hash" => $item->ja3_hash, "sni" => $item->sni, "apps" => []];
            }else if($request->input("input_type") == "JA4_SNI"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja4_hash', $item->ja4_hash);
                    $query->where('hashes.sni', $item->sni);
                });
                $results[] = ["ja4_hash" => $item->ja4_hash, "sni" => $item->sni, "apps" => []];
            }else if($request->input("input_type") == "JA3_JA3S_SNI"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item->ja3_hash);
                    $query->where('hashes.ja3s_hash', $item->ja3s_hash);
                    $query->where('hashes.sni', $item->sni);
                });
                $results[] = ["ja3_hash" => $item->ja3_hash, "ja3s_hash" => $item->ja3s_hash, "sni" => $item->sni, "apps" => []];
            }else if($request->input("input_type") == "JA4_JA4S_SNI"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja4_hash', $item->ja4_hash);
                    $query->where('hashes.ja4s_hash', $item->ja4s_hash);
                    $query->where('hashes.sni', $item->sni);
                });
                $results[] = ["ja4_hash" => $item->ja4_hash, "ja4s_hash" => $item->ja4s_hash, "sni" => $item->sni, "apps" => []];
            }else if($request->input("input_type") == "JA3_JA3S_SNI_JA4_JA4S"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item->ja3_hash);
                    $query->where('hashes.ja3s_hash', $item->ja3s_hash);
                    $query->where('hashes.sni', $item->sni);
                    $query->where('hashes.ja4_hash', $item->ja4_hash);
                    $query->where('hashes.ja4s_hash', $item->ja4s_hash);
                });
                $results[] = [
                    "ja3_hash" => $item->ja3_hash, "ja3s_hash" => $item->ja3s_hash, "sni" => $item->sni, 
                    "ja4_hash" => $item->ja4_hash, "ja4s_hash" => $item->ja4s_hash, "apps" => [], 
                ];
            }
            
        }

        $data = $query->get();

        foreach ($data as $item){
            $app_data = [
                "app_name" => $item->name, "package_name" => $item->package_name,
                "app_version" => $item->version, "is_malware" => $item->is_malware,
                "is_dangerous" => $item->is_dangerous,
            ];
            foreach($results as &$result){
            
                if ($request->input("input_type") == "JA3"){
                    if($item->ja3_hash == $result["ja3_hash"]){
                        array_push($result["apps"], $app_data);
                    }
                }else if ($request->input("input_type") == "JA4"){
                    if($item->ja4_hash == $result["ja4_hash"]){
                        array_push($result["apps"], $app_data);
                    }
                }else if ($request->input("input_type") == "JA3_JA3S"){
                    if($item->ja3_hash == $result["ja3_hash"] && $item->ja3s_hash == $result["ja3s_hash"]){
                        array_push($result["apps"], $app_data);
                    }
                }else if ($request->input("input_type") == "JA4_JA4S"){
                    if($item->ja4_hash == $result["ja4_hash"] && $item->ja4s_hash == $result["ja4s_hash"]){
                        array_push($result["apps"], $app_data);
                    }
                }else if ($request->input("input_type") == "JA3_SNI"){
                    if($item->ja3_hash == $result["ja3_hash"] && $item->sni == $result["sni"]){
                        array_push($result["apps"], $app_data);
                    }
                }else if ($request->input("input_type") == "JA4_SNI"){
                    if($item->ja4_hash == $result["ja4_hash"] && $item->sni == $result["sni"]){
                        array_push($result["apps"], $app_data);
                    }
                }else if ($request->input("input_type") == "JA3_JA3S_SNI"){
                    if($item->ja3_hash == $result["ja3_hash"] && $item->ja3s_hash == $result["ja3s_hash"] && $item->sni == $result["sni"]){
                        array_push($result["apps"], $app_data);
                    }
                }else if ($request->input("input_type") == "JA4_JA4S_SNI"){
                    if($item->ja4_hash == $result["ja4_hash"] && $item->ja4s_hash == $result["ja4s_hash"] && $item->sni == $result["sni"]){
                        array_push($result["apps"], $app_data);
                    }
                }else if ($request->input("input_type") == "JA3_JA3S_SNI_JA4_JA4S"){
                    if($item->ja3_hash == $result["ja3_hash"] && $item->ja3s_hash == $result["ja3s_hash"] && 
                    $item->sni == $result["sni"] && $item->ja4_hash == $result["ja4_hash"] && $item->ja4s_hash == $result["ja4s_hash"]){
                        array_push($result["apps"], $app_data);
                    }
                }
            }
        }

        foreach($results as &$result){
            $apps = collect($result["apps"])->unique();
            $result["apps"] = $apps->values()->all();
        }


        return response()->json($results, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createHashFromAPK(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'auth_key' => 'required|string',
            'apk_file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[2]);

        $apk_file_name = $this->saveApkFile($request->file('apk_file'));
        $apk_original_file_name = $request->file('apk_file')->getClientOriginalName();
        $process_id = uniqid('ext_api_', true);
        $channel_id = null;
        $hash_types = ["JA3"];

        $job_id = CreateHashFromAPK::dispatch(
            $apk_file_name,
            $hash_types,
            $request->ip(),
            $channel_id,
            $process_id,
        )->onQueue('process_queue');

        return response()
            ->json('Task for create hashes from APK ('.$apk_original_file_name.') was added to queue.', 200);
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
    public function createHashFromPackageName(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'auth_key' => 'required|string',
            'package_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[3]);

        $process_id = 'ext_api_'.Str::random(20);
        $hash_types = ["JA3"];
        $channel_id = null;

        $job = CreateHashFromAppName::dispatch(
            $request->input('package_name'),
            $hash_types,
            $request->ip(),
            $channel_id,
            $process_id,
        )->onQueue('process_queue');

        return response()->json('Task was added to queue succesfully.', 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createHashFromPcap(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'auth_key' => 'required|string',
            'app_name' => 'required|string',
            'package_name' => 'required|string',
            'app_version' => 'required|string',
            'pcap_file' => 'required|file',
            'is_malware' => 'required',
            'is_dangerous' => 'required',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[4]);

        $app_data = [
            'app_name' => $request->input('app_name'),
            'package_name' => $request->input('package_name'),
            'app_version' => $request->input('app_version'),
            'is_malware' => $request->input('is_malware'),
            'is_dangerous' => $request->input('is_dangerous')
        ];

        $pcap_file_name = $this->savePcapFile($request->file('pcap_file'));
        $hash_types = ["JA3"];

        $pcap_hash = new CreateHashFromPcap($pcap_file_name, $hash_types);
        $hashes = $pcap_hash->createAndSave($app_data);

        return response()->json($hashes, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function analyzePcapFile(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'auth_key' => 'required|string',
            'pcap_file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[6]);

        $pcap_file_name = $this->savePcapFile($request->file('pcap_file'));
        $hash_types = ["JA3"];

        $pcap_hash = new CreateHashFromPcap($pcap_file_name, $hash_types);
        $pcap_hashes = $pcap_hash->create();
        $response = [];

        foreach($hash_types as $hash_type){
            foreach($pcap_hashes[$hash_type] as $hash){
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

                $response[$hash] = $apps;
            }
        }

        return response()->json($response, 200);
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
     * @return void
     */
    public function insertHashes(Request $request): void {

        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[4]);

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

    /**
     * @param $auth_key
     * @param $ip_address
     * @param $type
     * @return void
     */
    private function registerApiRequest($auth_key, $ip_address, $type): void {

        $user = User::where('api_auth_key', '=', $auth_key)->first();

        $api_request = ApiRequest::create([
            'user_id' => $user->id,
            'ip_address' => $ip_address,
            'type' => $type,
        ]);

        //TODO: Description column of api request

        $api_request->save();
    }

    /**
     * 
     */
    public function analyzeFlowMonFile(Request $request): JsonResponse {

        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[5]);

        $validator = Validator::make($request->all(), [
            'flowmon_file' => 'required|mimes:csv',
            'hash_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $netflow_data = $this->getNetflowData($request->file('flowmon_file'));

        $results = [];
        
        $query = DB::table('applications')->select()->distinct()
        ->join('hashes', 'hashes.app_id', '=', 'applications.id');

        foreach ($netflow_data as $item){
            
            if (!is_string($item["ja3_hash"])){
                continue;
            }

            if($request->input("hash_type") == "JA3"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item["ja3_hash"]);
                });
                $results[] = ["ja3_hash" => $item["ja3_hash"], "apps" => []];
            }

            if($request->input("hash_type") == "JA3_SNI"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item["ja3_hash"])
                    ->where('hashes.sni', $item["sni"]);
                });
                $results[] = ["ja3_hash" => $item["ja3_hash"], "sni" => $item["sni"], "apps" => []];
            }
        }

        $data = $query->get();

        foreach ($data as $item){
            foreach($results as &$result){
                if ($request->input("hash_type") == "JA3"){
                    if($item->ja3_hash == $result["ja3_hash"]){
                        array_push($result["apps"], [
                            "app_name" => $item->name, 
                            "package_name" => $item->package_name,
                            "app_version" => $item->version,
                            "is_malware" => $item->is_malware,
                            "is_dangerous" => $item->is_dangerous,
                        ]);
                    }
                }
                if ($request->input("hash_type") == "JA3_SNI"){
                    if($item->ja3_hash == $result["ja3_hash"] && $item->sni == $result["sni"]){
                        array_push($result["apps"], [
                            "app_name" => $item->name, 
                            "package_name" => $item->package_name,
                            "app_version" => $item->version,
                            "is_malware" => $item->is_malware,
                            "is_dangerous" => $item->is_dangerous,
                        ]);
                    }
                }
            }
        }

        foreach($results as &$result){
            $apps = collect($result["apps"])->unique();
            $result["apps"] = $apps->values()->all();
        }

        return response()->json($results, 200);
    }


    protected function getNetflowData($netflow_file){

        $file = fopen($netflow_file->getPathname(), "r");

        $rows = [];
        $header = fgetcsv($file);
        $header = array_map("trim", $header);

        while ($row = fgetcsv($file)) {
            $rows[] = array_combine($header, $row);
        }
        
        $data = [];
        foreach ($rows as $row){
            $obj = [
                "ja3_hash" => ($row["TLS_JA3_FINGERPRINT"] == 'NIL') ?: strtolower($row["TLS_JA3_FINGERPRINT"]),
                "sni" => ($row["TLS_SNI"] == 'NIL') ?: strtolower($row["TLS_SNI"])
            ];
            $data[] = $obj;
        }

        return $data;
    }
}
