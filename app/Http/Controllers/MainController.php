<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Hook;
use DB;

class MainController extends Controller {

	public function getHooks(Request $request) {
		/*
		$hooks = DB::table('hooks')
			->join('users', 'hooks.user_id', '=', 'users.id')
			->select('hooks.id', 'hooks.title', 'hooks.votes', 'hooks.created_at', 'hooks.user_id', 'users.username')
			->take(20)->get();
		*/
		$hooks = DB::table('hooks')->select('id', 'title', 'user_id', 'description', 'votes', 'created_at')->take(10)->inRandomOrder()->get();
		
		return response()->json($hooks);
	}

	public function getHookDetails(Request $request, $id) {
		$hook_id = $id;
		$hook = DB::table('hooks')
		->join('users', 'hooks.user_id', '=', 'users.id')
			->select('hooks.*', 'users.username')
			->first();

		$comments = DB::table('hook_comments')
			->join('users', 'hook_comments.user_id', '=', 'users.id')
			->select('hook_comments.*', 'users.username')
			->where('hook_comments.hook_id', $hook_id)
			->take(20)->get();

		$output['hook']=$hook;
		$output['comments']=$comments;
		
		return response()->json($output);
	}

}
