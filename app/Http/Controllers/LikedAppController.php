<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\LikedApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class LikedAppController extends Controller
{
    //

    public function index(Request $request){

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $liked_apps = LikedApp::where("user_id", "=", $request->user_id)->pluck("app_id")->toArray();
        $apps = Application::all();

        $results = [];
        foreach($apps as $app){
            $item = ["id" => $app->id,"name" => $app->name, "liked" => false];
            if(in_array($app->id, $liked_apps)){
                $item["liked"] = true;
            }
            $results[] = $item;
        }

        return response()->json($results, 200);
    }

    public function editLikedApps(Request $request){

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'data' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $liked_apps = LikedApp::where("user_id", "=", $request->user_id)->pluck("app_id")->toArray();

        foreach($request->data as $liked_app){

            if($liked_app["liked"] == true){
                LikedApp::firstOrCreate(["user_id" => $request->user_id, "app_id" => $liked_app["id"]]);
            }else{
                if(in_array($liked_app["id"], $liked_apps)){
                    LikedApp::where("user_id", "=", $request->user_id)->where("app_id", "=", $liked_app["id"])->delete();
                }
            }
            
        }

        return response()->json([$request->data], 200);
    }

    public function getLikedAppsHashes(Request $request){

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $liked_apps = LikedApp::where("user_id", "=", $request->user_id)->get();

        if(count($liked_apps) == 0){
            return response()->json([], 200);
        }

        $query = DB::table('applications')->select(
            'applications.name as app_name','applications.package_name as package_name',
            'applications.version as app_version','hashes.ja3_hash as ja3_hash',
            'hashes.sni as sni', 'hashes.ja3s_hash as ja3s_hash', 
            'hashes.ja4_hash as ja4_hash', 'hashes.ja4s_hash as ja4s_hash'
        )->distinct()
        ->join('hashes', 'hashes.app_id', '=', 'applications.id');

        foreach($liked_apps as $liked_app){
            $query->orWhere(function ($query) use ($liked_app) {
                $query->where('applications.id', $liked_app->app_id);
            });
        }

        $results = $query->get();

        return response()->json($results, 200);
    }
}
