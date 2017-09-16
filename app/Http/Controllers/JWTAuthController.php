<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\User;
use Hash;

class JwtAuthController extends Controller {

    public function register(Request $request) {
      // limit userData to only the essential columns
      $userData = $request->only(User::$registrationFields);

      // create validator with validation rules found in User
      $validator = Validator::make($userData, User::$registrationValidationRules);

      // check inputs against validator
      if($validator->fails()) {
        return response()->json(['error'=>$validator->errors()->all()]);
      }

      // Hash the user's password
      $userData['password'] = Hash::make($userData['password']);

      // unguard and reguard are probably unecessary
      // they remove the mass-assignment restriction of User::$fillable
      // User::unguard();
      $user = User::create($userData);
      // User::reguard();

      // make sure a new entry was created in the db
      if(!$user->id) {
        return response()->json(['error'=>'Could not create user']);
      }

      // Successfully created the user in db, move directly to login with all inputs
      return $this->login($request);
    }

    public function login(Request $request) {
        $credentials = $request->only('email', 'password');

        try {
            // verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error'=>'invalid_credentials']);
            }
        } catch (JWTException $e) {
            // something went wrong
            return response()->json(['error'=>'could_not_create_token']);
        }

        // if no errors are encountered we can return a JWT
        return response()->json(['jwt'=>$token]);
    }
}
