<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller {

    /**
     * @brief The function ensures getting applications data for main website
     * @return JsonResponse Applications data
     */
    public function getApplicationDataForWeb(): JsonResponse {
        $applications = DB::table('applications')
            ->distinct()
            ->join('hashes','applications.id','=','hashes.app_id')
            ->get();

        return response()->json($applications);
    }

    /**
     * @brief The function ensures getting applications hashes for main website
     * @param string $app_name Application name
     * @param array $hash_types Hash types
     * @return array Hash objects
     */
    public function getAppHashes(string $app_name, array $hash_types): array {
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

    /**
     * @brief The function ensures getting application versions
     * @param string $app_name Application name
     * @return Collection Application versions
     */
    private function getApplicationVersions(string $app_name): Collection {
        return DB::table('applications')
            ->select('applications.version')
            ->where('applications.name','=',$app_name)
            ->distinct()
            ->pluck('version');
    }

    /**
     * @brief The function ensures getting application hashes
     * @param string $hash_type Hash type
     * @param string $app_name Application name
     * @param string $app_version Application version
     * @return Collection App hashes
     */
    private function getApplicationHashes(string $hash_type, string $app_name, string $app_version): Collection {
        return DB::table('applications')
            ->join('hashes','applications.id','=','hashes.app_id')
            ->select('hashes.hash')
            ->where('applications.name','=',$app_name)
            ->where('hashes.hash_type','=', $hash_type)
            ->where('applications.version','=',$app_version)
            ->pluck('hash');
    }
}
