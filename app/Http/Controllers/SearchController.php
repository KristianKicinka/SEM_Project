<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{

    public function index(Request $request){

        $url = "https://serpapi.com/search.json";
        $api_key = "e694fba92d38dbfb77e6d4fe838fba1e7e259465ed7a1a519400cabf4453a593";

        $query = [
            "engine" => "google_play",
            "store" => "apps",
            "q" => $request->input("app_name"),
            "api_key" => $api_key,
        ];

        $response = Http::withHeaders(['Accept'=>'application/json'])->get($url, $query)->json();

        return response()->json($response);
    }

    private function getPackageName(Request $request){

        $python_process = new Process(['python3', '../scripts/AnalyzePcapFile.py', $request->input('name')]);
        $python_process->run();

        if (!$python_process->isSuccessful()) {
            return "Python Error!";
        }

        $package_name = $python_process->getOutput();

       /* $adb_process = new Process(['sh','../scripts/adbRun.sh',$package_name]);
        $adb_process->run();

        if (!$adb_process->isSuccessful()) {
            return "ADB Error!";
        }
        */

        return $package_name;
    }
}
