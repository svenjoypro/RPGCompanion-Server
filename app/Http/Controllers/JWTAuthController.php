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
use Mail;
use App\PasswordReset;
use DB;

require_once(app_path().'/constants.php');

class JwtAuthController extends Controller {

	public function register(Request $request) {
		if (!$request->has('password') || !$request->has('username') || !$request->has('email')) {
            return response()->json(['error'=>'missing_data'], 400);
        }

		// limit userData to only the essential columns
		$userData = $request->only(User::$registrationFields);

		//Validation
        if (strlen($userData['password']) < 6) {
            return response()->json(['error'=>'invalid_password'], 400);
        }
        if (User::where('username', $userData['username'])->exists()) {
            return response()->json(['error'=>'username_in_use'], 400);
        }
        if (User::where('email', $userData['email'])->exists()) {
            return response()->json(['error'=>'email_in_use'], 400);
        }

		if ($userData['username'] == "admin" || $userData['username'] == "administrator") {
			return response()->json(['error'=>'invalid_parameters'], 400);
		}

		// Hash the user's password
		$userData['password'] = Hash::make($userData['password']);

		// Create random string for email confirmation
        $userData['email_confirmation_code'] = str_random(EMAIL_CONFIRMATION_LENGTH);

		$user = User::create($userData);

		// make sure a new entry was created in the db
		if (!$user->id) {
			return response()->json(['error'=>'db_error'], 500);
		}

		//TODO This takes a few seconds to complete and the user is left waiting. 
        //TODO We can leave it as is, they have to wait for the email anyway...
        //TODO Or we can implement Laravel Queueing. I vote we leave it as is.
		$email=$request->input('email');
		try {
			Mail::send('emails.verify', array('confirmation_code' => $userData['email_confirmation_code'], 'email' => $email), 
				function($message) use($email) {
					$message
						->to($email, $email)
						->subject('Verify your email address');
				}
			);
		}
		catch(Exception $e) {
			$user->delete();
			return response()->json(['error'=>'unable_to_send_email'], 500);
		}

		return response()->json(['success'=>'confirmation_sent']);
	}

	public function resendEmail(Request $request) {
		if (!$request->has('email')) {
			return response()->json(['error'=>'missing_data'], 400);
		}

		$email = $request->input('email');

		$user = User::where('email', $email)->first();
		if (!$user) {
			return response()->json(['error'=>'invalid_email'], 400);
		}
		if ($user->account_status != ACCOUNT_STATUS_UNCONFIRMED) {
			return response()->json(['error'=>'account_already_confirmed'], 400);
		}
		try {
			if (is_null($user->email_confirmation_code) || strlen($user->email_confirmation_code) != EMAIL_CONFIRMATION_LENGTH) {
				$user->email_confirmation_code = str_random(EMAIL_CONFIRMATION_LENGTH);
				$user->save();
			}
			Mail::send('emails.verify', array('confirmation_code' => $user->email_confirmation_code, 'email' => $email), 
				function($message) use($email) {
					$message
						->to($email, $email)
						->subject('Verify your email address');
				}
			);
		}
		catch(Exception $e) {
			$user->delete();
			return response()->json(['error'=>'could_not_send_email'], 500);
		}
		return response()->json(['success'=>'email_sent']);
	}

	public function createPasswordReset(Request $request) {
		if (!$request->has('email')) {
			return response()->json(['error'=>'missing_data'], 400);
		}

		$email = $request->input('email');
		$rand = str_random(EMAIL_CONFIRMATION_LENGTH);

		//Make sure token is unique
		while (PasswordReset::where('token', $rand)->count()!=0) {
			$rand = str_random(EMAIL_CONFIRMATION_LENGTH);			
		}

		if (User::where('email', $email)->count() == 0) { return response()->json(['error'=>'invalid_email'], 400); }

		$pr = PasswordReset::firstOrNew(array('email' => $email));
		$pr->token = $rand;
		if ($pr->save()) {
			try {
				Mail::send('emails.reset', array('token' => $rand, 'email' => $email), function($message) use($email) {
					$message->to($email, $email)
						->subject('Reset your password');
				});
				return response()->json(['success'=>'email_sent']);
			}
			catch(Exception $e) {
				$pr->delete();
				return response()->json(['error'=>'could_not_create_reset'], 500);
			}
		}
		return response()->json(['error'=>'db_error'], 500);
	}

	public function showResetPassword(Request $request) {
		//TODO create basic form with two inputs, post to self with hidden token
		return 'Please access this link from the app.';
	}

	public function resetPassword(Request $request) {
		if (!$request->has('t') || !$request->has('password')) { return response()->json(['error'=>'missing_data'], 400); }

		$pr = PasswordReset::where('token', $request->input('t'))->first();
		if (!$pr) { return response()->json(['error'=>'invalid_email'], 400); }
		if ($pr->token != $request->input('t')) { return response()->json(['error'=>'invalid_reset_token'], 400); }

		$user = User::where('email', $pr->email)->first();
		if (!$user) { response()->json(['error'=>'invalid_email'], 400); }

		$user->password = Hash::make($request->input('password'));
		if ($user->save()) {
			$pr->delete();
			return response()->json(['success'=>'password_updated_successfully']);
		}
		return response()->json(['error'=>'db_error'], 500);
	}

	public function login(Request $request) {
		$credentials = $request->only('email', 'password');

		/*
		//TO USE USERNAME INSTEAD OF EMAIL
        // Change email to username
        $credentials = $request->only('username', 'password');
        */

		try {
			// verify the credentials and create a token for the user
			if (! $token = JWTAuth::attempt($credentials)) {
				return response()->json(['error'=>'invalid_credentials'], 401);
			}
		} catch (JWTException $e) {
			// something went wrong
			return response()->json(['error'=>'could_not_create_token'], 500);
		}
		$user = Auth::User();
		//make sure the user has verified their email
		if ($user->account_status == ACCOUNT_STATUS_UNCONFIRMED) {
			return response()->json(['error'=>'account_unconfirmed'], 401);
		}

		// if no errors are encountered we can return a JWT
		return response()->json(['jwt'=>$token, 'id'=>$user->id, 'username'=>$user->username, 'email'=>$user->email, 'account_status'=>$user->account_status]);
	}

	public function confirm(Request $request) {
		if (!$request->has('c') || strlen($request->input('c')) != EMAIL_CONFIRMATION_LENGTH) {
			if ($request->has('app')) {
				return response()->json(['error'=>'invalid_code'], 400);
			}
			else {
				return "Invalid Code";
			}
		}

		$user = User::whereEmailConfirmationCode($request->input('c'))->first();
		if (!$user || $user->account_status != ACCOUNT_STATUS_UNCONFIRMED) {
			if ($request->has('app')) {
				return response()->json(['error'=>'invalid_code'], 400);
			}
			else {
				return "Invalid Code";
			}
		}

		$user->account_status = ACCOUNT_STATUS_CONFIRMED;
		$user->email_confirmation_code = null;

		if (!$user->save()) {
			if ($request->has('app')) {
				return response()->json(['error'=>'db_error'], 500);
			}
			else {
				return "Database Error, please try again.";
			}
		}

		if ($request->has('app')) {
			$token = JWTAuth::fromUser($user);
			return response()->json(['success'=>'account_verified', 'jwt'=>$token]);
		}
		else {
			return "Account verified successfully. You may now log in.";
		}
	}

	public function getAccountInfo(Request $request) {
		$user = Auth::User();

		$o['username']=$user->username;
		$o['email']=$user->email;
		$o['created_at']=$user->created_at;

		$maps = DB::table('maps')
				->where('user_id', $user->id)
				->pluck('id');
		$o['map_ups'] = DB::table('map_votes')
				->where('vote', 1)
				->whereIn('map_id', $maps)
				->count();
		$o['map_downs'] = DB::table('map_votes')
				->where('vote', 0)
				->whereIn('map_id', $maps)
				->count();

		$riddles = DB::table('riddles')
				->where('user_id', $user->id)
				->pluck('id');
		$o['riddle_ups'] = DB::table('riddle_votes')
				->where('vote', 1)
				->whereIn('riddle_id', $riddles)
				->count();
		$o['riddle_downs'] = DB::table('riddle_votes')
				->where('vote', 0)
				->whereIn('riddle_id', $riddles)
				->count();

		$hooks = DB::table('hooks')
				->where('user_id', $user->id)
				->pluck('id');
		$o['hook_ups'] = DB::table('hook_votes')
				->where('vote', 1)
				->whereIn('hook_id', $hooks)
				->count();
		$o['hook_downs'] = DB::table('hook_votes')
				->where('vote', 0)
				->whereIn('hook_id', $hooks)
				->count();

		$o['maps']=count($maps);
		$o['riddles']=count($riddles);
		$o['hooks']=count($hooks);

		return response()->json($o);
	}

	public function checkToken(Request $request) {
		return response()->json(['success'=>'valid_token']);
	}
}
