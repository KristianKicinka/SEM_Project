<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Jobs\CreateHash;
use App\Models\Process as ProcessModel;


class HashController extends Controller {

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createHash(Request $request){
        $ip_address = $request->ip();
        $file_name = $request->input('file_name');
        $input_type = $request->input('input_type');
        $hash_types = $request->input('hash_types');
        $frontend_id = $request->input('frontend_id');

        CreateHash::dispatch($file_name, $hash_types, $input_type, $frontend_id, $ip_address)
            ->onQueue('default');

        return response()->json('process '.$frontend_id.' is in queue');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getProcessInfo(Request $request){
        $frontend_id = $request->input('frontend_id');
        $info = ProcessModel::select('status','progress','message')->where('frontend_id','=',$frontend_id)->first();

        return response()->json($info);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getProcessResults(Request $request){

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
    public function getAppHashAPI(Request $request){

        $app_name = $request->input('params')['app_name'];
        $hash_types = $request->input('params')['hash_types'];

        $response = (new DatabaseController)->getAppHashes($app_name, $hash_types);

        return response()->json($response);
    }

}
