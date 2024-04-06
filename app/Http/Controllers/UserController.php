<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller {

    /**
     * @brief The function ensures the getting of all users in database system
     * @return JsonResponse Users objects with data
     */
    public function getUsersForAdmin(): JsonResponse {
        $users = DB::table('users')->select('id', 'name','surname', 'email', 'phone', 'role')->get();

        return response()->json(['users' => $users]);
    }

    /**
     * @brief The function ensures the creation of new system users
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
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

    /**
     * @brief The function ensures the deleting of specified user from system
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function deleteUser(Request $request): JsonResponse {
        DB::table('users')->where('id','=',$request->user_id)->delete();
        return response()->json(['status' => 'success'], 200);
    }

    /**
     * @brief The function ensures the updating of specified user data
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function updateUserData(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'name' => 'required',
            'surname' => 'required',
            'email' => ['required', 'email', Rule::unique('users')->ignore($request->user_id)],
            'phone' => ['required', Rule::unique('users')->ignore($request->user_id)],
            'role' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        User::where('id','=',$request->user_id)->update([
            'name' => $request->name,
            'surname'=> $request->surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
        ]);

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * @brief The function ensures the updating of specified user password
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function updateUserPassword(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        User::where('id','=',$request->user_id)->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['status' => 'success'], 200);
    }
}
