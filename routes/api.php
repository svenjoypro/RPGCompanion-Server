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

Route::get('hooks', 'MainController@getHooks');
Route::get('hooks/{id}', 'MainController@getHookDetails');

Route::middleware('auth:api')->get('/user', function (Request $request) {
	return $request->user();
});

Route::post('login', 'JWTAuthController@login');
Route::post('register', 'JWTAuthController@register');

Route::group(['middleware' => 'jwt.auth'], function () {
	Route::get('admin/info', 'AdminController@accountInfo');
});

Route::post('comments/post', 'CommentController@postComment');

