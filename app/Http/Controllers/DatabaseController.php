<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    public function getDatabaseData(){

        $applications = DB::table('applications')
                    ->join('hashes','applications.id','=','hashes.app_id')
                    ->get();

        return response()->json($applications);
    }
}
