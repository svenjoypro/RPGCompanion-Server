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

require_once(app_path().'/constants.php');

class JwtAuthController extends Controller {

	public function register(Request $request) {
		// limit userData to only the essential columns
		$userData = $request->only(User::$registrationFields);

		// create validator with validation rules found in User
		$validator = Validator::make($userData, User::$registrationValidationRules);

		// check inputs against validator
		if($validator->fails()) {
			return response()->json(['error'=>$validator->errors()->all()], 400);
		}

		if ($userData['username'] == "admin" || $userData['username'] == "administrator") {
			return response()->json(['error'=>'invalid_parameters'], 400);
		}

		// Hash the user's password
		$userData['password'] = Hash::make($userData['password']);

		// Create random string for email confirmation
        $userData['email_confirmation_code'] = str_random(EMAIL_CONFIRMATION_LENGTH);

		// unguard and reguard are probably unecessary
		// they remove the mass-assignment restriction of User::$fillable
		// User::unguard();
		$user = User::create($userData);
		// User::reguard();

		// make sure a new entry was created in the db
		if(!$user->id) {
			return response()->json(['error'=>'Could not create user'], 500);
		}

		$email=$request->input('email');
		
		try {
			Mail::send('emails.verify', array('confirmation_code' => $userData['email_confirmation_code'], 'email' => $email), function($message) use($email) {
				$message->to($email, $email)
					->subject('Verify your email address');
			});
		}
		catch(Exception $e) {
			$user->delete();
			return response()->json(['error'=>'could_not_create_user'], 500);
		}

		return response()->json(['success'=>'Confirmation Email Sent']);
	}

	public function resendEmail(Request $request) {
		if (!$request->has('email')) {
			return response()->json(['error'=>'missing_data'], 400);
		}

		$email = $request->input('email');

		$user = User::where('email', $email)->first();
		if (!$user) {
			return response()->json(['error'=>'invalid_email']);
		}
		try {
			if (is_null($user->email_confirmation_code) || strlen($user->email_confirmation_code) != EMAIL_CONFIRMATION_LENGTH) {
				$user->email_confirmation_code = str_random(EMAIL_CONFIRMATION_LENGTH);
				$user->save();
			}
			Mail::send('emails.verify', array('confirmation_code' => $user->email_confirmation_code, 'email' => $email), function($message) use($email) {
				$message->to($email, $email)
					->subject('Verify your email address');
			});
		}
		catch(Exception $e) {
			$user->delete();
			return response()->json(['error'=>'could_not_create_user'], 500);
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
		return response()->json(['jwt'=>$token]);
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

	public function checkToken(Request $request) {
		return response()->json(['success'=>'valid_token']);
	}
}
