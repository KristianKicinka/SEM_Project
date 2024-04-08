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
            'email' => 'required|email:strict|unique:users',
            'phone' => 'required|regex:/^((\+)?[0-9]{3} ?)?[0-9]{3} ?[0-9]{3} ?[0-9]{3} ?$/m|unique:users',
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
            'user_id' => 'required|numeric',
            'name' => 'required|string',
            'surname' => 'required|string',
            'email' => 'required|email:strict|unique:users',
            'phone' => 'required|regex:/^((\+)?[0-9]{3} ?)?[0-9]{3} ?[0-9]{3} ?[0-9]{3} ?$/m|unique:users',
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

    /**
     * @brief The function ensures editing user profile in basic user interface
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function editBasicUser(Request $request): JsonResponse {

        // Request data validator
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
            'name' => 'required|string',
            'surname' => 'required|string',
            'email' => 'required|email:strict|unique:users',
            'phone' => 'required|regex:/^((\+)?[0-9]{3} ?)?[0-9]{3} ?[0-9]{3} ?[0-9]{3} ?$/m|unique:users',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        User::where('id', '=', $request->user_id)->update([
            'name' => $request->name,
            'surname'=> $request->surname,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        // Updated user
        $user = User::find($request->user_id);

        return response()->json($user, 200);
    }

    /**
     * @brief The function ensures changing password in user GUI
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function changePasswordBasicUser(Request $request): JsonResponse {

        // Request data validator
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'password' => 'required|min:8',
            'password_re' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Update password
        $password = bcrypt($request->password);
        User::where('id', '=', $request->user_id)->update([ 'password' => $password,]);

        return response()->json(['status' => 'success'], 200);
    }
}
