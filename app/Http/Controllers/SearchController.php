<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SearchController extends Controller
{
    
    public function index(Request $request){
        
        $package_name = SearchController::getPackageName($request);

        return response()->json($package_name);
    }

    private function getPackageName(Request $request){

        $python_process = new Process(['python3', '../scripts/Search.py', $request->input('name')]);
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
