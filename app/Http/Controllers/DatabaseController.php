<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller {

    public function getDatabaseData(){
        $applications = DB::table('applications')
                    ->join('hashes','applications.id','=','hashes.app_id')
                    ->get();

        return response()->json($applications);
    }

    public function getAppHashes($app_name, $hash_types){
        $response = [];
        $hashes = [];

        $app_versions = $this->getApplicationVersions($app_name);
    
        foreach($app_versions as $app_version){
            foreach($hash_types as $hash_type){
                
                $db_items = $this->getApplicationHashes($hash_type, $app_name, $app_version);
                $hashes[$hash_type] = $db_items;
            }

            $resp_item = [
                'version' => $app_version,
                'hashes' => $hashes
            ];

            $response[] = $resp_item;
        }
        return $response;
    }

    private function getApplicationVersions($app_name){
        return DB::table('applications')
                    ->select('applications.version')
                    ->where('applications.name','=',$app_name)
                    ->distinct()
                    ->pluck('version');
    }

    private function getApplicationHashes($hash_type, $app_name, $app_version){
        return DB::table('applications')
                    ->join('hashes','applications.id','=','hashes.app_id')
                    ->select('hashes.hash')
                    ->where('applications.name','=',$app_name)
                    ->where('hashes.hash_type','=', $hash_type)
                    ->where('applications.version','=',$app_version)
                    ->pluck('hash');
    }
    
}
