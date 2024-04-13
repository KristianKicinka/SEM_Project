<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Jobs\CreateHashFromAPK;
use App\Jobs\CreateHashFromAppName;
use App\Objects\CreateHashFromPcap;
use App\Models\Process as ProcessModel;

use App\Models\Application;
use App\Models\Hash;
use \App\Exceptions\HashGeneratorFailException;

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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $ip_address = $request->ip();
        $channel_id = $request->input('channel_id');
        $hash_types = json_decode($request->input('hash_types'));
        $processes = json_decode($request->input('processes'), true);

        $apk_files = $request->file("files");

        foreach ($apk_files as $apk_file){
            $process_name = $apk_file->getClientOriginalName();
            $apk_file_name = $this->saveApkFile($apk_file);
            $process_id = collect($processes)->where('name', $process_name)->pluck('process_id')->first();

            CreateHashFromAPK::dispatch(
                $apk_file_name,
                $hash_types,
                $ip_address,
                $channel_id,
                $process_id,
                $process_name,
            )->onQueue('process_queue');
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
        // Request data validator
        $validator = Validator::make($request->all(), [
            'channel_id' => 'required|string',
            'package_name' => 'required|string',
            'hash_types' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $ip_address = $request->ip();
        $channel_id = $request->input('channel_id');
        $process_id = uniqid('int_api_', true);

        CreateHashFromAppName::dispatch(
            $request->package_name,
            $request->hash_types,
            $ip_address,
            $channel_id,
            $process_id,
            )->onQueue('process_queue');

        $process = [
            "process_id" => $process_id, "name" => $request->package_name,
            "message" => "Waiting in queue", "progress" => 0, "status" => "processing"
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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $hash_types = json_decode($request->input('hash_types'));
        $ip_address = $request->ip();
        $channel_id = $request->input("channel_id");
        $text_file_path = $this->saveTextFile($request->file('text_file'));

        $package_names = file($text_file_path);

        foreach($package_names as $package_name){
            $process_id = uniqid('int_api_', true);

            CreateHashFromAppName::dispatch(
                trim($package_name),
                $hash_types,
                $ip_address,
                $channel_id,
                $process_id,
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
                'hashes.sni as sni', 'hashes.ja3s_hash as ja3s_hash',
                'hashes.ja4_hash as ja4_hash', 'hashes.ja4s_hash as ja4s_hash', 'hashes.ja4x_hash as ja4x_hash'
            )
            ->join('hashes','processes.id','=','hashes.process_id')
            ->join('applications','applications.id','=','hashes.app_id')
            ->where('processes.job_id','=',$request->input("process_id"))
            ->get();

        return response()->json($results, 200);
    }

    /**
     * @brief The function ensures getting hash data in admin panel
     * @return JsonResponse Hashes from database
     */
    public function getHashesForAdmin(): JsonResponse {

        $data = DB::table('applications')
            ->join('hashes','applications.id','=','hashes.app_id')
            ->select('hashes.id', 'ja3_hash','sni', 'ja3s_hash','ja4_hash','ja4s_hash', 'ja4x_hash',
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
