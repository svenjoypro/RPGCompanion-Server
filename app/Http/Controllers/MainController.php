<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Hook;
use DB;

class MainController extends Controller {

	public function getWebalert(Request $request) {
		$o['msg']="Update: 10/24/17 \nNPCs are now editable, click any attribute to be able to edit it, however it may create compatibility issues with any previous NPCs you have saved. You may have to delete your saved NPCs. Sorry for any inconveniences.";
		return response()->json($o);
	}

	public function getHooks(Request $request) {
		$hooks = DB::table('hooks')->select('id', 'title', 'user_id', 'description', 'created_at')->take(10)->inRandomOrder()->get();

		foreach ($hooks as $hook) {
			$hook->upvotes = DB::table('hook_votes')->where('hook_id', $hook->id)->where('vote', '1')->count();
			$hook->downvotes = DB::table('hook_votes')->where('hook_id', $hook->id)->where('vote', '0')->count();
			
			if ($hook->voted = DB::table('hook_votes')->select('vote')->where('hook_id', $hook->id)->where('user_id', '0')->first()) {}
			else { $hook->voted = -1; }
		}

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
