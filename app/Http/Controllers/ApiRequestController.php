<?php

namespace App\Http\Controllers;

use App\Exceptions\HashGeneratorFailException;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Jobs\CreateHashFromAPK;
use App\Jobs\CreateHashFromAppName;
use App\Models\ApiRequest;

use App\Objects\CreateHashFromPcap;

// API request types
const REQUEST_TYPES = [
    "get_app_hashes", "get_apps_from_hashes", "create_hash_from_apk",
    "create_hash_from_package_name", "create_hash_from_pcap",
    "analyze_flowmon_file", "analyze_pcap_file",
];

class ApiRequestController extends Controller {

    /**
     * @brief The function ensures API auth key generation
     * @param Request $request HTTP request data
     * @return JsonResponse New auth API key
     */
    public function generateApiKey(Request $request): JsonResponse {

        $auth_api_key = Str::random(30);
        User::where('id','=',$request->user_id)->update([
            'api_auth_key' => $auth_api_key,
        ]);

        return response()->json(['auth_api_key' => $auth_api_key]);
    }

    /**
     * @brief The function ensures return an API auth key to user
     * @param Request $request HTTP request data
     * @return JsonResponse User auth API key
     */
    public function getApiKey(Request $request): JsonResponse {
        $auth_api_key = User::select('api_auth_key')->where('id','=',$request->user_id)->first();
        return response()->json($auth_api_key);
    }

    /**
     * @brief The function ensures return all API requests from database
     * @return JsonResponse All API requests
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
     * @brief The function ensures return user's API requests
     * @param Request $request HTTP request data
     * @return JsonResponse All user's API requests
     */
    public function getRequestsUser(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Select all API request for requested user
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

    /**
     * @brief The function ensures creation validator rules for hash array
     * @param Request $request HTTP request data
     * @return \Illuminate\Validation\Validator Validator object
     */
    private function validateHashItems(Request $request): \Illuminate\Validation\Validator {
        $rules = [];

        // Create validator rules
        if($request->input("input_type") == "JA3"){
            $rules = ['hashes.*.ja3_hash' => 'required|string'];
        }else if ($request->input("input_type") == "JA3_JA3S"){
            $rules = ['hashes.*.ja3_hash' => 'required|string', 'hashes.*.ja3s_hash' => 'required|string'];
        }else if ($request->input("input_type") == "JA3_JA3S_SNI"){
            $rules = ['hashes.*.ja3_hash' => 'required|string', 'hashes.*.ja3s_hash' => 'required|string',
                'hashes.*.sni' => 'required|string'
            ];
        }else if ($request->input("input_type") == "JA4"){
            $rules = ['hashes.*.ja4_hash' => 'required|string'];
        }else if ($request->input("input_type") == "JA4_JA4S"){
            $rules = ['hashes.*.ja4_hash' => 'required|string', 'hashes.*.ja4s_hash' => 'required|string'];
        }else if ($request->input("input_type") == "JA4_JA4S_SNI"){
            $rules = ['hashes.*.ja4_hash' => 'required|string', 'hashes.*.ja4s_hash' => 'required|string',
                'hashes.*.sni' => 'required|string'
            ];
        }else if ($request->input("input_type") == "JA3_JA3S_SNI_JA4_JA4S"){
            $rules = ['hashes.*.ja3_hash' => 'required|string', 'hashes.*.ja3s_hash' => 'required|string',
                'hashes.*.sni' => 'required|string', 'hashes.*.ja4_hash' => 'required|string',
                'hashes.*.ja4s_hash' => 'required|string'
            ];
        }

        return Validator::make($request->all(), $rules);
    }

    // External API functions

    /**
     * @brief The function ensures return all hashes that belongs to requested applications
     * @param Request $request HTTP request data
     * @return JsonResponse Applications hashes
     */
    public function getAppHashes(Request $request): JsonResponse {

        // Validator rules
        $rules = [
            'auth_key' => 'required|string',
            'apps' => 'required|array',
            'apps.*.package_name' => 'required|string',
            'apps.*.version' => 'required|string',
        ];

        // Validator error messages
        $messages = [
            'auth_key.required' => 'The auth key is required.',
            'auth_key.string' => 'The auth key must be string.',
            'apps.required' => 'The "apps" field is required.',
            'apps.array' => 'The "apps" field must be an array.',
            'apps.*.package_name.required' => 'The "package_name" field is required for each app object.',
            'apps.*.version.required' => 'The "version" field is required for each app object.',
            'apps.*.package_name.string' => 'The "package_name" field must be string.',
            'apps.*.version.string' => 'The "version" field must be string.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // API request registration
        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[0]);
        $results = [];

        foreach ($request->input('apps') as $app){
            $hashes =  DB::table('applications')
                ->select('hashes.ja3_hash', 'hashes.ja3s_hash', 'hashes.sni',
                    'hashes.ja4_hash', 'hashes.ja4s_hash')
                ->join('hashes','hashes.app_id','=','applications.id');

            if($app["package_name"] != null)
                $hashes->where('applications.package_name','=', $app["package_name"]);
            if($app["version"] != null)
                $hashes->where('applications.version','=', $app["version"]);

            $results[$app["package_name"]] = $hashes->get();
        }

        return response()->json($results, 200);
    }

    /**
     * @brief The function ensures return all apps connected with requested hashes
     * @param Request $request HTTP request data
     * @return JsonResponse All applications data
     */
    public function getAppsFromHashes(Request $request): JsonResponse {

        // Validator rules
        $rules = [
            'auth_key' => 'required|string',
            'hashes' => 'required|array',
            'input_type' => 'required|string',
        ];

        // Validator error messages
        $messages = [
            'auth_key.required' => 'The auth key is required.',
            'auth_key.string' => 'The auth key must be string.',
        ];

        // Validator for HTTP request
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Validator for hashes array items
        $hashes_validator = $this->validateHashItems($request);

        if ($hashes_validator->fails()) {
            return response()->json(['errors' => $hashes_validator->errors()], 400);
        }

        // API request registration
        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[1]);
        $results = [];

        // Static select part
        $query = DB::table('applications')->select(
            'applications.name', 'applications.package_name', 'applications.version',
            'applications.is_malware', 'applications.is_dangerous', 'hashes.ja3_hash',
            'hashes.ja3s_hash', 'hashes.ja4_hash', 'hashes.ja4s_hash', 'hashes.sni',
            )
            ->distinct()
            ->join('hashes', 'hashes.app_id', '=', 'applications.id');

        // Dynamic select part
        foreach ($request->input('hashes') as $item){

            if($request->input("input_type") == "JA3"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item["ja3_hash"]);
                });
                $results[] = ["ja3_hash" => $item["ja3_hash"], "apps" => []];
            }else if($request->input("input_type") == "JA4"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja4_hash', $item["ja4_hash"]);
                });
                $results[] = ["ja4_hash" => $item["ja4_hash"], "apps" => []];
            }else if($request->input("input_type") == "JA3_JA3S"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item["ja3_hash"]);
                    $query->where('hashes.ja3s_hash', $item["ja3s_hash"]);
                });
                $results[] = ["ja3_hash" => $item["ja3_hash"], "ja3s_hash" => $item["ja3s_hash"], "apps" => []];
            }else if($request->input("input_type") == "JA4_JA4S"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja4_hash', $item["ja4_hash"]);
                    $query->where('hashes.ja4s_hash', $item["ja4s_hash"]);
                });
                $results[] = ["ja4_hash" => $item["ja4_hash"], "ja4s_hash" => $item["ja4s_hash"], "apps" => []];
            }else if($request->input("input_type") == "JA3_SNI"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item["ja3_hash"]);
                    $query->where('hashes.sni', $item["sni"]);
                });
                $results[] = ["ja3_hash" => $item["ja3_hash"], "sni" => $item["sni"], "apps" => []];
            }else if($request->input("input_type") == "JA4_SNI"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja4_hash', $item["ja4_hash"]);
                    $query->where('hashes.sni', $item["sni"]);
                });
                $results[] = ["ja4_hash" => $item["ja4_hash"], "sni" => $item["sni"], "apps" => []];
            }else if($request->input("input_type") == "JA3_JA3S_SNI"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item["ja3_hash"]);
                    $query->where('hashes.ja3s_hash', $item["ja3s_hash"]);
                    $query->where('hashes.sni', $item["sni"]);
                });
                $results[] = [
                    "ja3_hash" => $item["ja3_hash"], "ja3s_hash" => $item["ja3s_hash"],
                    "sni" => $item["sni"], "apps" => []
                ];
            }else if($request->input("input_type") == "JA4_JA4S_SNI"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja4_hash', $item["ja4_hash"]);
                    $query->where('hashes.ja4s_hash', $item["ja4s_hash"]);
                    $query->where('hashes.sni', $item["sni"]);
                });
                $results[] = ["ja4_hash" => $item["ja4_hash"], "ja4s_hash" => $item["ja4s_hash"],
                    "sni" => $item["sni"], "apps" => []
                ];
            }else if($request->input("input_type") == "JA3_JA3S_SNI_JA4_JA4S"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item["ja3_hash"]);
                    $query->where('hashes.ja3s_hash', $item["ja3s_hash"]);
                    $query->where('hashes.sni', $item["sni"]);
                    $query->where('hashes.ja4_hash', $item["ja4_hash"]);
                    $query->where('hashes.ja4s_hash', $item["ja4s_hash"]);
                });
                $results[] = [
                    "ja3_hash" => $item["ja3_hash"], "ja3s_hash" => $item["ja3s_hash"], "sni" => $item["sni"],
                    "ja4_hash" => $item["ja4_hash"], "ja4s_hash" => $item["ja4s_hash"], "apps" => [],
                ];
            }

        }

        // Database select
        $data = $query->get();

        // Data processing
        foreach ($data as $item){
            $app_data = [
                "app_name" => $item->name, "package_name" => $item->package_name,
                "app_version" => $item->version, "is_malware" => $item->is_malware,
                "is_dangerous" => $item->is_dangerous,
            ];
            foreach($results as &$result){

                if ($request->input("input_type") == "JA3"){
                    if($item->ja3_hash == $result["ja3_hash"]){
                        $result["apps"][] = $app_data;
                    }
                }else if ($request->input("input_type") == "JA4"){
                    if($item->ja4_hash == $result["ja4_hash"]){
                        $result["apps"][] = $app_data;
                    }
                }else if ($request->input("input_type") == "JA3_JA3S"){
                    if($item->ja3_hash == $result["ja3_hash"] && $item->ja3s_hash == $result["ja3s_hash"]){
                        $result["apps"][] = $app_data;
                    }
                }else if ($request->input("input_type") == "JA4_JA4S"){
                    if($item->ja4_hash == $result["ja4_hash"] && $item->ja4s_hash == $result["ja4s_hash"]){
                        $result["apps"][] = $app_data;
                    }
                }else if ($request->input("input_type") == "JA3_SNI"){
                    if($item->ja3_hash == $result["ja3_hash"] && $item->sni == $result["sni"]){
                        $result["apps"][] = $app_data;
                    }
                }else if ($request->input("input_type") == "JA4_SNI"){
                    if($item->ja4_hash == $result["ja4_hash"] && $item->sni == $result["sni"]){
                        $result["apps"][] = $app_data;
                    }
                }else if ($request->input("input_type") == "JA3_JA3S_SNI"){
                    if($item->ja3_hash == $result["ja3_hash"] && $item->ja3s_hash == $result["ja3s_hash"]
                        && $item->sni == $result["sni"]){
                        $result["apps"][] = $app_data;
                    }
                }else if ($request->input("input_type") == "JA4_JA4S_SNI"){
                    if($item->ja4_hash == $result["ja4_hash"] && $item->ja4s_hash == $result["ja4s_hash"]
                        && $item->sni == $result["sni"]){
                        $result["apps"][] = $app_data;
                    }
                }else if ($request->input("input_type") == "JA3_JA3S_SNI_JA4_JA4S"){
                    if($item->ja3_hash == $result["ja3_hash"] && $item->ja3s_hash == $result["ja3s_hash"] &&
                    $item->sni == $result["sni"] && $item->ja4_hash == $result["ja4_hash"]
                        && $item->ja4s_hash == $result["ja4s_hash"]){
                        $result["apps"][] = $app_data;
                    }
                }
            }
        }

        // Remove duplicities
        foreach($results as &$result){
            $apps = collect($result["apps"])->unique();
            $result["apps"] = $apps->values()->all();
        }

        return response()->json($results, 200);
    }

    /**
     * @brief The function ensures the creation of hashes from APK file
     * @param Request $request HTTP request data
     * @return JsonResponse API response
     */
    public function createHashFromAPK(Request $request): JsonResponse {

        // Validator rules
        $rules = [
            'auth_key' => 'required|string',
            'apk_file' => 'required|file|mimes:apk,zip',
        ];

        // Validator error messages
        $messages = [
            'auth_key.required' => 'The auth key is required.',
            'auth_key.string' => 'The auth key must be string.',
            'apk_file.required' => 'APK file is required.',
            'apk_file.file' => 'APK file must be file.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // API request registration
        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[2]);

        $apk_file_name = $this->saveApkFile($request->file('apk_file'));
        $apk_original_file_name = $request->file('apk_file')->getClientOriginalName();
        $process_id = uniqid('ext_api_', true);
        $channel_id = null;
        $hash_types = ["JA3"];

        // Dispatching queue job
        CreateHashFromAPK::dispatch(
            $apk_file_name, $hash_types, $request->ip(), $channel_id, $process_id,
        )->onQueue('process_queue');

        return response()
            ->json(
                'Task for create hashes from APK ('.$apk_original_file_name.') was added to queue.', 200
            );
    }

    /**
     * @brief The function ensure saving APK files to local storage on server
     * @param UploadedFile $file APK file to save
     * @return string Saved file name
     */
    private function saveApkFile(UploadedFile $file): string {
        $file_name = $file->getClientOriginalName();
        $final_name = date('his') .'_'. $file_name;
        $file->storeAs('uploads/apk_inserted',$final_name,'public');

        return $final_name;
    }

    /**
     * @brief The function ensures the creation of hashes from application package name
     * @param Request $request HTTP request data
     * @return JsonResponse API response
     */
    public function createHashFromPackageName(Request $request): JsonResponse {

        // Validator rules
        $rules = [
            'auth_key' => 'required|string',
            'package_name' => 'required|string',
        ];

        // Validator error messages
        $messages = [
            'auth_key.required' => 'The auth key is required.',
            'auth_key.string' => 'The auth key must be string.',
            'package_name.required' => 'The package name is required.',
            'package_name.string' => 'The package name must be string.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // API request registration
        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[3]);

        $process_id = 'ext_api_'.Str::random(20);
        $hash_types = ["JA3"];
        $channel_id = null;

        // Dispatching queue job
        CreateHashFromAppName::dispatch(
            $request->input('package_name'), $hash_types,
            $request->ip(), $channel_id, $process_id,
        )->onQueue('process_queue');

        $response = "Task for create hashes from package name (";
        $response = $response.$request->input("package_name").") was added to queue.";
        return response()->json($response, 200);
    }

    /**
     * @brief The function ensures the creation of hashes from PCAP file
     * @param Request $request HTTP request data
     * @return JsonResponse API response
     * @throws HashGeneratorFailException Hash generator fail exception
     */
    public function createHashFromPcap(Request $request): JsonResponse {

        // Validator rules
        $rules = [
            'auth_key' => 'required|string', 'app_name' => 'required|string',
            'package_name' => 'required|string', 'app_version' => 'required|string',
            'pcap_file' => 'required|file|mimes:pcap', 'is_malware' => 'required',
            'is_dangerous' => 'required',
        ];

        // Validator error messages
        $messages = [
            'auth_key.required' => 'The auth key is required.',
            'auth_key.string' => 'The auth key must be string.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // API request registration
        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[4]);

        $app_data = [
            'app_name' => $request->input('app_name'), 'package_name' => $request->input('package_name'),
            'app_version' => $request->input('app_version'), 'is_malware' => $request->input('is_malware'),
            'is_dangerous' => $request->input('is_dangerous')
        ];

        $pcap_file_name = $this->savePcapFile($request->file('pcap_file'));
        $hash_types = ["JA3"];

        // Create new hashes
        $pcap_hash = new CreateHashFromPcap($pcap_file_name, $hash_types);
        $hashes = $pcap_hash->createAndSave($app_data);

        return response()->json($hashes, 200);
    }

    /**
     * @brief The function ensures PCAP file analysis
     * @param Request $request HTTP request data
     * @return JsonResponse Analysis output
     * @throws HashGeneratorFailException Hash generator fail exception
     */
    public function analyzePcapFile(Request $request): JsonResponse {

        // Validator rules
        $rules = [
            'auth_key' => 'required|string',
            'pcap_file' => 'required|file|mimes:pcap',
        ];

        // Validator error messages
        $messages = [
            'auth_key.required' => 'The auth key is required.',
            'auth_key.string' => 'The auth key must be string.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // API request registration
        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[6]);

        $pcap_file_name = $this->savePcapFile($request->file('pcap_file'));
        $hash_types = ["JA3"];

        // Create hashes from PCAP file
        $pcap_hash = new CreateHashFromPcap($pcap_file_name, $hash_types);
        $pcap_hashes = $pcap_hash->create();
        $response = [];

        // Process hashes by hash type
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
     * @brief The function ensures saving PCAP files to local storage
     * @param UploadedFile $file PCAP file
     * @return string PCAP file name
     */
    private function savePcapFile(UploadedFile $file): string {

        $file_name = $file->getClientOriginalName();
        $final_name = date('his') .'_'. $file_name;
        $file->storeAs('uploads/pcap_inserted',$final_name,'public');

        return $final_name;
    }

    /**
     * @brief The function ensures API request registration
     * @param String $auth_key User auth key
     * @param String $ip_address User IP address
     * @param String $type API request type
     * @return void
     */
    private function registerApiRequest(String $auth_key, String $ip_address, String $type): void {

        $user = User::where('api_auth_key', '=', $auth_key)->first();

        $api_request = ApiRequest::create([
            'user_id' => $user->id,
            'ip_address' => $ip_address,
            'type' => $type,
        ]);

        $api_request->save();
    }

    /**
     * @brief The function ensures Netflow file analysis
     * @param Request $request HTTP request data
     * @return JsonResponse Analysis output
     */
    public function analyzeNetFlowFile(Request $request): JsonResponse {

        // Validator rules
        $rules = [
            'auth_key' => 'required|string',
            'flowmon_file' => 'required|mimes:csv',
            'hash_type' => 'required|string',
        ];

        // Validator messages
        $messages = [
            'auth_key.required' => 'The auth key is required.',
            'auth_key.string' => 'The auth key must be string.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // API request registration
        $this->registerApiRequest($request->input('auth_key'), $request->ip(), REQUEST_TYPES[5]);

        $netflow_data = $this->getNetflowData($request->file('flowmon_file'));
        $results = [];

        // Static part of database select
        $query = DB::table('applications')->select()->distinct()
        ->join('hashes', 'hashes.app_id', '=', 'applications.id');

        // Process netflow data
        foreach ($netflow_data as $item){

            if (!is_string($item["ja3_hash"])){
                continue;
            }

            // Get JA3 hashes
            if($request->input("hash_type") == "JA3"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item["ja3_hash"]);
                });
                $results[] = ["ja3_hash" => $item["ja3_hash"], "apps" => []];
            }

            // Get JA3_SNI hashes
            if($request->input("hash_type") == "JA3_SNI"){
                $query->orWhere(function ($query) use ($item) {
                    $query->where('hashes.ja3_hash', $item["ja3_hash"])
                    ->where('hashes.sni', $item["sni"]);
                });
                $results[] = ["ja3_hash" => $item["ja3_hash"], "sni" => $item["sni"], "apps" => []];
            }
        }

        // Final select
        $data = $query->get();

        foreach ($data as $item){
            foreach($results as &$result){
                // Process JA3 hashes
                if ($request->input("hash_type") == "JA3"){
                    if($item->ja3_hash == $result["ja3_hash"]){
                        $result["apps"][] = [
                            "app_name" => $item->name, "package_name" => $item->package_name,
                            "app_version" => $item->version, "is_malware" => $item->is_malware,
                            "is_dangerous" => $item->is_dangerous,
                        ];
                    }
                }
                // Process JA3_SNI hashes
                if ($request->input("hash_type") == "JA3_SNI"){
                    if($item->ja3_hash == $result["ja3_hash"] && $item->sni == $result["sni"]){
                        $result["apps"][] = [
                            "app_name" => $item->name, "package_name" => $item->package_name,
                            "app_version" => $item->version, "is_malware" => $item->is_malware,
                            "is_dangerous" => $item->is_dangerous,
                        ];
                    }
                }
            }
        }

        // Remove duplicities
        foreach($results as &$result){
            $apps = collect($result["apps"])->unique();
            $result["apps"] = $apps->values()->all();
        }

        return response()->json($results, 200);
    }


    /**
     * @brief The function ensures processing Netflow CSV file
     * @param UploadedFile $netflow_file Netflow CSV file
     * @return array Mobile apps hashes
     */
    protected function getNetflowData(UploadedFile $netflow_file): array {

        // Load Netflow CSV file
        $file = fopen($netflow_file->getPathname(), "r");

        $rows = [];
        $header = fgetcsv($file);
        $header = array_map("trim", $header);

        // Process csv rows
        while ($row = fgetcsv($file)) {
            $rows[] = array_combine($header, $row);
        }

        $data = [];
        // Create data from rows
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
