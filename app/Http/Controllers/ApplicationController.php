<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller {
    //

    /**
     * @return JsonResponse
     */
    public function getApplicationDataForWeb(): JsonResponse {
        $applications = DB::table('applications')
                    ->distinct()
                    ->join('hashes','applications.id','=','hashes.app_id')
                    ->get();

        return response()->json($applications);
    }

    /**
     * @param $app_name
     * @param $hash_types
     * @return array
     */
    public function getAppHashes($app_name, $hash_types): array {
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

    private function getApplicationVersions($app_name): Collection {
        return DB::table('applications')
                    ->select('applications.version')
                    ->where('applications.name','=',$app_name)
                    ->distinct()
                    ->pluck('version');
    }

    /**
     * @param $hash_type
     * @param $app_name
     * @param $app_version
     * @return Collection
     */
    private function getApplicationHashes($hash_type, $app_name, $app_version): Collection {
        return DB::table('applications')
                    ->join('hashes','applications.id','=','hashes.app_id')
                    ->select('hashes.hash')
                    ->where('applications.name','=',$app_name)
                    ->where('hashes.hash_type','=', $hash_type)
                    ->where('applications.version','=',$app_version)
                    ->pluck('hash');
    }
}
