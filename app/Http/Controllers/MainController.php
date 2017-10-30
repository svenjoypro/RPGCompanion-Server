<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Hook;
use DB;

class MainController extends Controller {

	public function getWebalert(Request $request) {
		$o['msg']="Update: 10/30/17 Happy Halloween! \nRiddles Improved - You shouldn't get any repeats anymore (until you leave the screen then come back). Also it shouldn't crash anymore when you click on a riddle (though not much happens when you click on one yet)";
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
		if ($request->has('ids')) {
			$riddles = DB::table('riddles')->select('id', 'riddle', 'user_id', 'answer', 'created_at')->whereIn('id', $request->input('ids'))->get();
		}
		else if ($request->has('random')) {
			$qty=5;
			if ($request->has('qty')) {
				$qty = intval($request->input('qty'));
			}
			$output['total'] = DB::table('riddles')->count();
			$riddles = DB::table('riddles')->select('id', 'riddle', 'user_id', 'answer', 'created_at')->take($qty)->inRandomOrder()->get();
		}
		else if ($request->has('sort')) {
			//todo
		}

		foreach ($riddles as $riddle) {
			$riddle->upvotes = DB::table('riddle_votes')->where('riddle_id', $riddle->id)->where('vote', '1')->count();
			$riddle->downvotes = DB::table('riddle_votes')->where('riddle_id', $riddle->id)->where('vote', '0')->count();
			
			if ($riddle->voted = DB::table('riddle_votes')->select('vote')->where('riddle_id', $riddle->id)->where('user_id', '0')->first()) {}
			else { $riddle->voted = -1; }
		}
		$output['riddles']=$riddles;
		return response()->json($output);
	}

	public function getRiddleDetails(Request $request, $id) {
		$riddle_id = $id;
		$riddle = DB::table('riddles')
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


	public function getPuzzles(Request $request) {
		$puzzles = DB::table('puzzles')->select('id', 'puzzle', 'user_id', 'answer', 'created_at')->take(5)->inRandomOrder()->get();

		foreach ($puzzles as $puzzle) {
			$puzzle->upvotes = DB::table('puzzle_votes')->where('puzzle_id', $puzzle->id)->where('vote', '1')->count();
			$puzzle->downvotes = DB::table('puzzle_votes')->where('puzzle_id', $puzzle->id)->where('vote', '0')->count();
			
			if ($puzzle->voted = DB::table('puzzle_votes')->select('vote')->where('puzzle_id', $puzzle->id)->where('user_id', '0')->first()) {}
			else { $puzzle->voted = -1; }
		}

		return response()->json($puzzles);
	}

	public function getPuzzleDetails(Request $request, $id) {
		$puzzle_id = $id;
		$puzzle = DB::table('puzzles')
		->join('users', 'puzzles.user_id', '=', 'users.id')
			->select('puzzles.*', 'users.username')
			->first();

		$comments = DB::table('puzzle_comments')
			->join('users', 'puzzle_comments.user_id', '=', 'users.id')
			->select('puzzle_comments.*', 'users.username')
			->where('puzzle_comments.puzzle_id', $puzzle_id)
			->take(20)->get();

		$output['puzzle']=$puzzle;
		$output['comments']=$comments;
		
		return response()->json($output);
	}

}
