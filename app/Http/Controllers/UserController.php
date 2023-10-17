<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller {

    /**
     * @return JsonResponse
     */
    public function getUsersForAdmin(): JsonResponse {
        $users = DB::table('users')->select('id', 'name','surname', 'email', 'phone', 'role')->get();

        return response()->json(['users' => $users]);
    }

    public function createUser(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'surname' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users',
            'password' => 'required|min:8',
            'role' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'surname'=> $request->surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $user->save();
        return response()->json(['status' => 'success'], 200);
    }

    public function deleteUser(Request $request): JsonResponse {
        DB::table('users')->where('id','=',$request->user_id)->delete();
        return response()->json(['status' => 'success'], 200);
    }

    public function updateUser(){

    }
}
