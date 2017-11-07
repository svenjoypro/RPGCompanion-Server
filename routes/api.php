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

Route::get('webalert', 'MainController@getWebalert');

Route::get('hooks', 'MainController@getHooks');
Route::get('hooks/{id}', 'MainController@getHookDetails');

Route::get('riddles', 'MainController@getRiddles');
Route::get('riddles/{id}', 'MainController@getRiddleDetails');

Route::middleware('auth:api')->get('/user', function (Request $request) {
	return $request->user();
});

Route::post('login', 'JWTAuthController@login');
Route::post('register', 'JWTAuthController@register');
Route::get('confirm-email', 'JWTAuthController@confirm');

Route::group(['middleware' => 'jwt.auth'], function () {
	Route::get('admin/check-token', 'JWTAuthController@checkToken');
	Route::get('admin/info', 'AdminController@accountInfo');
});

Route::post('comments/post', 'CommentController@postComment');


