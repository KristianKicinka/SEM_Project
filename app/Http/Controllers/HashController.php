<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Jobs\CreateHashFromAPK;
use App\Jobs\CreateHashFromAppName;
use App\Models\Process as ProcessModel;
use PhpParser\Node\Stmt\TryCatch;

class HashController extends Controller {

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createHashFromAPK(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'frontend_id' => 'required|string',
            'hash_types' => 'required',
            'apk_file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $ip_address = $request->ip();
        $frontend_id = $request->input('frontend_id');
        $hash_types = json_decode($request->input('hash_types'));
        $apk_file_name = $this->saveApkFile($request->file('apk_file'));

        CreateHashFromAPK::dispatch($apk_file_name, $hash_types, $frontend_id, $ip_address)
            ->onQueue('default');

        return response()->json('process '.$frontend_id.' is in queue');
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
    public function createHashFromAppName(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'package_name' => 'required|string',
            'frontend_id' => 'required|string',
            'hash_types' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $ip_address = $request->ip();
        $frontend_id = $request->input('frontend_id');

        CreateHashFromAppName::dispatch($request->package_name, $request->hash_types, $frontend_id, $ip_address)
            ->onQueue('default');

        return response()->json('process '.$frontend_id.' is in queue');
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

        $frontend_id = $request->input('frontend_id');
        
        $info = ProcessModel::select('status','progress','message')->where('frontend_id','=',$frontend_id)->first();

        return response()->json($info);
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
            ->where('processes.frontend_id','=',$frontend_id)
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

        return response()->json($results);
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

        return response()->json($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getHashesForAdmin(Request $request): JsonResponse {
        // ["id", "hash", "hash_type", "app_name", "package_name", "version"];
        $data = DB::table('applications')
            ->join('hashes','applications.id','=','hashes.app_id')
            ->select('hashes.id', 'hash','hash_type', 'name AS app_name', 'package_name', 'version')
            ->get();

        return response()->json($data);
    }
}
