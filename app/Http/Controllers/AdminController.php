<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\User;

class AdminController extends Controller {

    public function accountInfo(Request $request) {
      $currentUser = JWTAuth::parseToken()->authenticate();

      return response()->json(['status'=>'success', 'username'=>$currentUser->username]);
    }

    public function updateAccount(Request $request) {
      $currentUser = JWTAuth::parseToken()->authenticate();

      /*
      if ($request->input('password')) {
        $currentUser->password = $request->input('password');
        $currentUser->save();
      }
      */

    }

}
