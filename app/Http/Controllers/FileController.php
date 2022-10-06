<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends Controller
{
    public function saveApkFile(Request $request){
        /*$request->validate([
            'file' => 'required|mimes:apk|max:2048',
        ]);
        */
        return response()->json('APK FILE SAVED!');
    }

    public function saveNamesListFile(Request $request){
        return response()->json('NAMES LIST FILE SAVED!');
    }
}
