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

        $process = new Process(['python3', '../scripts/Search.py', $request->input('name')]);
        $process->run();

        if (!$process->isSuccessful()) {
            return "Python Error!";
        }
        
        return $process->getOutput();
    }
}
