<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Hook;
use DB;

class MainController extends Controller {

	public function getWebalert(Request $request) {
		$o['msg']="Update: 10/28/17 \nRiddles now implemented\nI currently only have 226 riddles (some are better than others), and when you click the \"Load More\" button you will eventually get repeats (just like with the Plot Hooks). I'm still debating the best way to prevent duplicates while still retrieving random results - I'm open to suggestions.";
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

	public function getRiddles(Request $request) {
		$riddles = DB::table('riddles')->select('id', 'riddle', 'user_id', 'answer', 'created_at')->take(5)->inRandomOrder()->get();

		foreach ($riddles as $riddle) {
			$riddle->upvotes = DB::table('riddle_votes')->where('riddle_id', $riddle->id)->where('vote', '1')->count();
			$riddle->downvotes = DB::table('riddle_votes')->where('riddle_id', $riddle->id)->where('vote', '0')->count();
			
			if ($riddle->voted = DB::table('riddle_votes')->select('vote')->where('riddle_id', $riddle->id)->where('user_id', '0')->first()) {}
			else { $riddle->voted = -1; }
		}

		return response()->json($riddles);
	}

	public function getRiddleDetails(Request $request, $id) {
		$riddle_id = $id;
		$riddle = DB::table('riddle')
		->join('users', 'riddles.user_id', '=', 'users.id')
			->select('riddles.*', 'users.username')
			->first();

		$comments = DB::table('riddle_comments')
			->join('users', 'riddle_comments.user_id', '=', 'users.id')
			->select('riddle_comments.*', 'users.username')
			->where('riddle_comments.riddle_id', $riddle_id)
			->take(20)->get();

		$output['riddle']=$riddle;
		$output['comments']=$comments;
		
		return response()->json($output);
	}

}
