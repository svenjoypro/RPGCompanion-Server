<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\HookVote;
use App\HookCommentVote;
use DB;
use App\RiddleVote;
use App\RiddleCommentVote;
use Auth;

class MainController extends Controller {

	public function getWebalert(Request $request) {
		$o['msg']="Update: 10/30/17 Happy Halloween! \nRiddles Improved - You shouldn't get any repeats anymore (until you leave the screen then come back). Also it shouldn't crash anymore when you click on a riddle (though not much happens when you click on one yet)";
		return response()->json($o);
	}
	/*
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
	*/

	public function vote(Request $request) {
		//-1=didn't vote, 0=downvoted, 1=upvoted
		if (!$request->has('type') || !$request->has('vote') || !is_numeric($request->input('vote')) || !$request->has('id') || !is_numeric($request->input('id'))) {
			return response()->json(['error'=>'invalid_parameters'], 400);
		}

		$user = Auth::user();
		$uid = $user->id;
		$id = intval($request->input('id'));
		$type = $request->input('type');
		$prop = $type."_id";
		$vote=intval($request->input('vote'));
		

		switch ($type) {
			case "hook":
				$prev = HookVote::where('user_id', $uid)->where('hook_id', $id)->first();
				if ($prev) {
					if ($prev->vote == $vote) { return response()->json(['success'=>'vote_unchanged']); }
					$prev->vote = $vote;
					if ($prev->save()) { return response()->json(['success'=>'vote_updated']); }
					else { return response()->json(['error'=>'invalid_parameters'], 400); }
				}
				$insert = new HookVote;
				break;
			case "hook_comment":
				$prev = HookCommentVote::where('user_id', $uid)->where('hook_comment_id', $id)->first();
				if ($prev) {
					if ($prev->vote == $vote) { return response()->json(['success'=>'vote_unchanged']); }
					$prev->vote = $vote;
					if ($prev->save()) { return response()->json(['success'=>'vote_updated']); }
					else { return response()->json(['error'=>'invalid_parameters'], 400); }
				}
				$insert = new HookCommentVote;
				break;
			case "riddle":
				$prev = RiddleVote::where('user_id', $uid)->where('riddle_id', $id)->first();
				if ($prev) {
					if ($prev->vote == $vote) { return response()->json(['success'=>'vote_unchanged']); }
					$prev->vote = $vote;
					if ($prev->save()) { return response()->json(['success'=>'vote_updated']); }
					else { return response()->json(['error'=>'invalid_parameters'], 400); }
				}
				$insert = new RiddleVote;
				break;
			case "riddle_comment":
				$prev = RiddleCommentVote::where('user_id', $uid)->where('riddle_comment_id', $id)->first();
				if ($prev) {
					if ($prev->vote == $vote) { return response()->json(['success'=>'vote_unchanged']); }
					$prev->vote = $vote;
					if ($prev->save()) { return response()->json(['success'=>'vote_updated']); }
					else { return response()->json(['error'=>'invalid_parameters'], 400); }
				}
				$insert = new RiddleCommentVote;
				break;
			default:
				return response()->json(['error'=>'invalid_parameters'], 400);
		}
		
		$insert->$prop = $id;
		$insert->user_id = $uid;
		$insert->vote = $vote;

		if ($insert->save()) { return response()->json(['success'=>'vote_saved']); }
		else { return response()->json(['error'=>'db_error'], 500); }
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
