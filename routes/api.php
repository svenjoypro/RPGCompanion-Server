<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('release-notes',					'MainController@getReleaseNotes');

Route::get('hooks', 						'HookController@getHooks'); // DEPRECATED
Route::post('hooks', 						'HookController@getHooks2');
Route::get('hooks/{id}', 					'HookController@getHookDetails');

Route::get('riddles', 						'RiddleController@getRiddles'); // DEPRECATED
Route::post('riddles', 						'RiddleController@getRiddles2');
Route::get('riddles/{id}', 					'RiddleController@getRiddleDetails');

Route::post('maps', 						'MapController@getMaps');
Route::get('maps/get-environments', 		'MapController@getEnvironments');

Route::get('maps/{id}', 					'MapController@getMapDetails');





/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
	return $request->user();
});
*/

Route::post('login', 						'JWTAuthController@login');
Route::post('register', 					'JWTAuthController@register');
Route::get('confirm-email', 				'JWTAuthController@confirm');
Route::post('resend-email', 				'JWTAuthController@resendEmail');
Route::post('create-password-reset', 		'JWTAuthController@createPasswordReset');
Route::get('reset-password', 				'JWTAuthController@showResetPassword');
Route::post('reset-password', 				'JWTAuthController@resetPassword');

Route::group(['middleware' => 'jwt.auth'], function () {

	Route::get('admin/check-token', 		'JWTAuthController@checkToken');
	//Route::get('admin/info', 				'AdminController@accountInfo');

	Route::post('hooks/new', 				'HookController@submitHook');
	Route::post('riddles/new', 				'RiddleController@submitRiddle');
	Route::post('maps/new',					'MapController@submitMap');

	Route::post('vote',						'MainController@vote');

	//Route::post('comments/post', 			'CommentController@postComment');

	Route::get('admin/account',				'JWTAuthController@getAccountInfo');
});




