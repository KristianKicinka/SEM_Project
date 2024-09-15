<?php
/**
 * @file ApplicationController.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * @brief The function ensures getting applications data for main website
     * @return JsonResponse Applications data
     */
    public function exportAppDataToCSV() {

        $data = DB::table('applications')
            ->select('hashes.id AS id','name','package_name','version','ja3_hash', 'sni', 'ja3s_hash',
            'ja4_hash', 'ja4s_hash', 'ja4x_hash', 'is_dangerous', 'is_malware',
            'ip_src', 'port_src', 'ip_dest', 'port_dest', 'hashes.created_at AS created_at')
            ->distinct()
            ->join('hashes','applications.id','=','hashes.app_id')
            ->get();

        // Streamed response to handle large datasets efficiently
        $response = new StreamedResponse(function() use ($data) {
            $handle = fopen('php://output', 'w');

            // Write the CSV column headers
            fputcsv($handle, [
                'ID', 'Name', 'Package Name', 'Version', 'JA3 Hash', 'SNI', 
                'JA3S Hash', 'JA4 Hash', 'JA4S Hash', 'JA4X Hash', 'Is Dangerous', 
                'Is Malware', 'IP Source', 'Port Source', 'IP Destination', 'Port Destination', 'Created At'
            ]);

            // Write each row of data
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row->id, $row->name, $row->package_name, $row->version, $row->ja3_hash, $row->sni, 
                    $row->ja3s_hash, $row->ja4_hash, $row->ja4s_hash, $row->ja4x_hash, $row->is_dangerous, 
                    $row->is_malware, $row->ip_src, $row->port_src, $row->ip_dest, $row->port_dest, $row->created_at
                ]);
            }

            // Close the file stream
            fclose($handle);
        });

        // Set appropriate headers for CSV download
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="hashapp-database.csv"');

        return $response;
    }
}
