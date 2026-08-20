<?php
/**
 * @file HashController.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use App\Jobs\CreateHashFromAPK;
use App\Jobs\CreateHashFromAppName;
use App\Objects\CreateHashFromPcap;
use App\Models\Process as ProcessModel;

use App\Models\Application;
use App\Models\Hash;
use \App\Exceptions\HashGeneratorFailException;
use App\Jobs\CreateHashFromXAPK;

const PCAP_PATH = 'app/public/uploads/pcap_inserted/';

class HashController extends Controller {

    /**
     * @brief The function ensures hash creation from apk files
     * @param Request $request HTTP request data
     * @return JsonResponse New processes and pusher channel ID
     */
    public function createHashFromAPK(Request $request): JsonResponse {
        // Request data validator
        $validator = Validator::make($request->all(), [
            'hash_types' => 'required',
            'channel_id' => 'required|string',
            'files.*' => 'required',
            'processes' => 'required',
            'custom_hash_types' => 'nullable|array',
            'custom_hash_types.*' => 'integer|exists:custom_hash_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $ip_address = $request->ip();
        $channel_id = $request->input('channel_id');
        $hash_types = json_decode($request->input('hash_types'));
        $processes = json_decode($request->input('processes'), true);

        // Add custom hash types if provided
        if ($request->has('custom_hash_types')) {
            $customHashTypes = \App\Models\CustomHashType::whereIn('id', $request->input('custom_hash_types'))
                ->where('is_active', true)
                ->where(function($query) use ($request) {
                    $user = auth()->user();
                    if ($user) {
                        $query->where('user_id', $user->id)
                              ->orWhere('is_public', true);
                    }
                })
                ->pluck('name')
                ->toArray();
            
            $hash_types = array_merge($hash_types, $customHashTypes);
        }

        $input_files = $request->file("files");

        foreach ($input_files as $input_file){
            $process_name = $input_file->getClientOriginalName();

            if (Str::endsWith($input_file->getClientOriginalName(), '.xapk')){
                $xapk_file_name = $this->saveXapkFile($input_file);
                $process_id = collect($processes)->where('name', $process_name)->pluck('process_id')->first();

                CreateHashFromXAPK::dispatch(
                    $xapk_file_name, $hash_types, $ip_address, $channel_id, $process_id, $process_name, null
                )->onQueue('process_queue');
            } else {
                $apk_file_name = $this->saveApkFile($input_file);
                $process_id = collect($processes)->where('name', $process_name)->pluck('process_id')->first();

                CreateHashFromAPK::dispatch(
                    $apk_file_name, $hash_types, $ip_address, $channel_id, $process_id, $process_name, null
                )->onQueue('process_queue');
            }
        }

        return response()->json([
            'processes' => $processes, "channel_id" => $request->input("channel_id")], 200);
    }

    /**
     * @brief The function ensures saving apk files
     * @param UploadedFile $file APK file
     * @return string APK file name
     */
    private function saveApkFile(UploadedFile $file): string {
        $file_name = $file->getClientOriginalName();
        $final_name = date('his') .'_'. $file_name;
        $file->storeAs('uploads/apk_inserted',$final_name,'public');

        return $final_name;
    }

    /**
     * @brief The function ensures saving xapk files
     * @param UploadedFile $file XAPK file
     * @return string XAPK file name
     */
    private function saveXapkFile(UploadedFile $file): string {
        $file_name = $file->getClientOriginalName();
        $final_name = date('his') .'_'. $file_name;
        $file->storeAs('uploads/xapk_inserted',$final_name,'public');

        return $final_name;
    }

    /**
     * @brief The function ensures hash creation from pcap files
     * @param Request $request HTTP request data
     * @return JsonResponse Created hashes
     * @throws HashGeneratorFailException Hash generator exception
     */
    public function createHashFromPcap(Request $request): JsonResponse {
        // Request data validator
        $validator = Validator::make($request->all(), [
            'app_name_pcap' => 'required|string',
            'package_name_pcap' => 'required|string',
            'pcap_file' => 'required|file',
            'is_malware_pcap' => 'required',
            'is_dangerous_pcap' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $app_data = [
            'app_name' => $request->input('app_name_pcap'),
            'package_name' => $request->input('package_name_pcap'),
            'app_version' => $request->input('app_version_pcap'),
            'is_malware' => $request->input('is_malware_pcap'),
            'is_dangerous' => $request->input('is_dangerous_pcap')
        ];

        $pcap_file_name = $this->savePcapFile($request->file('pcap_file'));
        $hash_types = ["JA3"];

        $pcap_hash = new CreateHashFromPcap($pcap_file_name, $hash_types);
        $hashes = $pcap_hash->createAndSave($app_data);

        return response()->json(['hashes' => $hashes], 200);
    }

    /**
     * @brief The function ensures saving pcap files to local storage
     * @param UploadedFile $file Pcap file
     * @return string Saved file path
     */
    private function savePcapFile(UploadedFile $file): string {
        $file_name = $file->getClientOriginalName();
        $final_name = date('his') .'_'. $file_name;
        $file->storeAs('uploads/pcap_inserted',$final_name,'public');

        return $final_name;
    }

    /**
     * @brief The function ensures hash creation from app name
     * @param Request $request HTTP request data
     * @return JsonResponse Hash generator processes
     */
    public function createHashFromAppName(Request $request): JsonResponse {
        Log::channel('devlog')->info('API createHashFromAppName called for package: {package}', ['package' => $request->package_name]);
        
        // Check for duplicate requests within last 5 seconds
        $cacheKey = 'hash_request_' . $request->package_name . '_' . $request->channel_id;
        if (cache()->has($cacheKey)) {
            Log::channel('devlog')->info('Duplicate request ignored for package: {package}', ['package' => $request->package_name]);
            return response()->json(['error' => 'Duplicate request ignored'], 429);
        }
        
        // Cache the request for 5 seconds to prevent duplicates
        cache()->put($cacheKey, true, 5);
        
        // Request data validator
        $validator = Validator::make($request->all(), [
            'channel_id' => 'required|string',
            'package_name' => 'required|string',
            'hash_types' => 'required',
            'custom_hash_types' => 'nullable|array',
            'custom_hash_types.*' => 'integer|exists:custom_hash_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $ip_address = $request->ip();
        $channel_id = $request->input('channel_id');
        $process_id = uniqid('int_api_', true);
        $hash_types = $request->hash_types;

        // Add custom hash types if provided
        if ($request->has('custom_hash_types')) {
            $customHashTypes = \App\Models\CustomHashType::whereIn('id', $request->input('custom_hash_types'))
                ->where('is_active', true)
                ->where(function($query) use ($request) {
                    $user = auth()->user();
                    if ($user) {
                        $query->where('user_id', $user->id)
                              ->orWhere('is_public', true);
                    }
                })
                ->pluck('name')
                ->toArray();
            
            $hash_types = array_merge($hash_types, $customHashTypes);
        }

        CreateHashFromAppName::dispatch(
            $request->package_name,
            $hash_types,
            $ip_address,
            $channel_id,
            $process_id,
            null
            )->onQueue('process_queue');

        $process = [
            "process_id" => $process_id, "name" => $request->package_name,
            "message" => "Waiting in queue", "progress" => 0, "status" => "in_queue"
        ];
        $processes[] = $process;

        return response()->json(['processes' => $processes], 200);
    }

    /**
     * @brief The function ensures hash creation from text input
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function createHashFromTextInput(Request $request): JsonResponse {
        // Request data validator
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string',
            'package_name' => 'required|string',
            'app_version' => 'required|string',
            'ja3_hash' => 'nullable|string',
            'ja3s_hash' => 'nullable|string',
            'sni' => 'nullable|string',
            'ja4_hash' => 'nullable|string',
            'ja4s_hash' => 'nullable|string',
            'ja4x_hash' => 'nullable|json',
            'is_malware' => 'required|bool',
            'is_dangerous' => 'required|bool',
            'ip_src' => 'required|ip',
            'port_src' => 'required|numeric',
            'ip_dest'=> 'required|ip',
            'port_dest' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $app_identifier = [
            'name' => $request->app_name,
            'package_name' => $request->package_name,
            'version' => $request->app_version,
            'is_malware' => $request->is_malware,
            'is_dangerous' => $request->is_dangerous,
        ];

        $new_application = [
            'name' => $request->app_name,
            'package_name' => $request->package_name,
            'version' => $request->app_version,
            'is_malware' => $request->is_malware,
            'is_dangerous' => $request->is_dangerous,
        ];

        $application = Application::firstOrCreate($app_identifier, $new_application);

        $hash_identifier = [
            'app_id' => $application->id,
            'ja3_hash' => $request->ja3_hash,
            'ja3s_hash' => $request->ja3_hash,
            'sni' => $request->sni,
            'ja4_hash' => $request->ja4_hash,
            'ja4s_hash' => $request->ja4s_hash,
            'ja4x_hash' => $request->ja4x_hash,
            'ip_src' => $request->ip_src,
            'port_src' => $request->port_src,
            'ip_dest' => $request->ip_dest,
            'port_dest' => $request->port_dest,
        ];

        $new_record = [
            'app_id' => $application->id,
            'ja3_hash' => $request->ja3_hash,
            'ja3s_hash' => $request->ja3_hash,
            'sni' => $request->sni,
            'ja4_hash' => $request->ja4_hash,
            'ja4s_hash' => $request->ja4s_hash,
            'ja4x_hash' => $request->ja4x_hash,
            'ip_src' => $request->ip_src,
            'port_src' => $request->port_src,
            'ip_dest' => $request->ip_dest,
            'port_dest' => $request->port_dest,
        ];

        Hash::firstOrCreate($hash_identifier, $new_record);

        return response()->json('process success!', 200);
    }

    /**
     * @brief The function ensures creating apps hashes from text file
     * @param Request $request HTTP request data
     * @return JsonResponse Hash generator processes
     */
    public function createHashFromTextFile(Request $request): JsonResponse {
        // Request data validator
        $validator = Validator::make($request->all(), [
            'text_file' => 'required|file|mimes:txt',
            'hash_types' => 'required',
            'channel_id' => 'required|string',
            'custom_hash_types' => 'nullable|array',
            'custom_hash_types.*' => 'integer|exists:custom_hash_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $hash_types = json_decode($request->input('hash_types'));
        $ip_address = $request->ip();
        $channel_id = $request->input("channel_id");
        $text_file_path = $this->saveTextFile($request->file('text_file'));

        // Add custom hash types if provided
        if ($request->has('custom_hash_types')) {
            $customHashTypes = \App\Models\CustomHashType::whereIn('id', $request->input('custom_hash_types'))
                ->where('is_active', true)
                ->where(function($query) use ($request) {
                    $user = auth()->user();
                    if ($user) {
                        $query->where('user_id', $user->id)
                              ->orWhere('is_public', true);
                    }
                })
                ->pluck('name')
                ->toArray();
            
            $hash_types = array_merge($hash_types, $customHashTypes);
        }

        $package_names = file($text_file_path);
        
        // Remove duplicates and empty lines
        $package_names = array_unique(array_filter(array_map('trim', $package_names)));
        
        // Debug log to check for duplicates
        Log::channel('devlog')->info('Package names after deduplication: {names}', ['names' => $package_names]);

        foreach($package_names as $package_name){
            $process_id = uniqid('int_api_', true);

            CreateHashFromAppName::dispatch(
                trim($package_name),
                $hash_types,
                $ip_address,
                $channel_id,
                $process_id,
                null
                )->onQueue('process_queue');

            $process = [
                "process_id" => $process_id, "name" => $package_name,
                "message" => "Waiting in queue", "progress" => 0, "status" => "processing"
            ];
            $processes[] = $process;
        }

        return response()->json(['processes' => $processes], 200);
    }

    /**
     * @brief The function ensures saving text files to local storage
     * @param UploadedFile $file Text file
     * @return string Saved file path
     */
    private function saveTextFile(UploadedFile $file): string {
        $file_name = $file->getClientOriginalName();
        $final_name = date('his') .'_'. $file_name;

        $file->storeAs('uploads/text_inserted',$final_name,'public');
        return storage_path('app/public/uploads/text_inserted/').$final_name;
    }

    /**
     * @brief The function ensures getting hash generation process info
     * @param Request $request HTTP request data
     * @return JsonResponse Process info
     */
    public function getProcessInfo(Request $request): JsonResponse {
        // Request data validator
        $validator = Validator::make($request->all(), [
            'identifiers' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $response = [];

        foreach(json_decode($request->input('identifiers')) as $id){
            $response[$id] = ProcessModel::select('status','progress','message')
                ->where('job_id','=',$id)->first();
        }

        return response()->json($response, 200);
    }

    /**
     * @brief The function ensures getting hash generation process results
     * @param Request $request HTTP request data
     * @return JsonResponse Process results
     */
    public function getProcessResults(Request $request): JsonResponse {
        // Request data validator
        $validator = Validator::make($request->all(), [
            'process_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $results = DB::table('processes')
            ->select(
                'applications.name as app_name','applications.package_name as package_name',
                'applications.version as app_version','hashes.ja3_hash as ja3_hash',
                'hashes.sni as sni', 'hashes.sni_flag as sni_flag', 'hashes.is_flagged as is_flagged',
                'hashes.ja3s_hash as ja3s_hash',
                'hashes.ja4_hash as ja4_hash', 'hashes.ja4s_hash as ja4s_hash', 'hashes.ja4x_hash as ja4x_hash',
                'hashes.custom_hashes as custom_hashes', 'hashes.created_at as created_at'
            )
            ->join('hashes','processes.id','=','hashes.process_id')
            ->join('applications','applications.id','=','hashes.app_id')
            ->where('processes.job_id','=',$request->input("process_id"))
            ->get();

        // Decode custom_hashes JSON strings to objects
        $results->transform(function ($item) {
            if ($item->custom_hashes) {
                $item->custom_hashes = json_decode($item->custom_hashes, true);
            }
            return $item;
        });

        return response()->json($results, 200);
    }

    /**
     * @brief The function ensures getting hash data in admin panel
     * @return JsonResponse Hashes from database
     */
    public function getHashesForAdmin(): JsonResponse {

        $data = DB::table('applications')
            ->join('hashes','applications.id','=','hashes.app_id')
            ->select('hashes.id', 'ja3_hash','sni', 'sni_flag', 'is_flagged', 'ja3s_hash','ja4_hash','ja4s_hash', 'ja4x_hash',
                'name AS app_name', 'package_name', 'version', 'is_dangerous', 'is_malware', 'ip_src',
                'port_src', 'ip_dest', 'port_dest'
            )
            ->distinct()
            ->get();

        return response()->json($data, 200);
    }

    /**
     * @brief The function ensures deleting hash data in admin panel
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function deleteHashAdmin (Request $request): JsonResponse {
        DB::table("hashes")->where("hashes.id", "=", $request->input("hash_id"))->delete();
        return response()->json("Hash deleted", 200);
    }

    /**
     * @brief The function ensures updating hash data in admin panel
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function updateHashAdmin (Request $request): JsonResponse {
        // Request data validator
        $validator = Validator::make($request->all(), [
            'hash_id' => 'required',
            'app_name' => 'required|string',
            'package_name' => 'required|string',
            'version' => 'required|string',
            'sni' => 'nullable|string',
            'ja3_hash' => 'nullable|string',
            'ja3s_hash' => 'nullable|string',
            'ja4_hash' => 'nullable|string',
            'ja4s_hash' => 'nullable|string',
            'ja4x_hash' => 'nullable|json',
            'is_malware' => 'required|bool',
            'is_dangerous' => 'required|bool',
            'ip_src' => 'required|ip',
            'port_src' => 'required|numeric',
            'ip_dest'=> 'required|ip',
            'port_dest' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        DB::table("hashes")
        ->where('hashes.id','=',$request->hash_id)
        ->join("applications", "applications.id","=","hashes.app_id")
        ->update([
            'applications.name' => $request->app_name,
            'applications.package_name' => $request->package_name,
            'applications.version' => $request->version,
            'hashes.sni' => $request->sni,
            'hashes.ja3_hash' => $request->ja3_hash,
            'hashes.ja3s_hash' => $request->ja3s_hash,
            'hashes.ja4_hash' => $request->ja4_hash,
            'hashes.ja4s_hash' => $request->ja4s_hash,
            'hashes.ja4x_hash' => $request->ja4x_hash,
            'applications.is_malware' => $request->is_malware,
            'applications.is_dangerous' => $request->is_dangerous,
            'ip_src' => $request->ip_src,
            'port_src' => $request->port_src,
            'ip_dest' => $request->ip_dest,
            'port_dest' => $request->port_dest,
        ]);

        return response()->json(['status' => 'success'], 200);
    }
}
