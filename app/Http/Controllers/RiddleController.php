<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Riddle;
use App\RiddleVote;
use DB;
use Auth;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class RiddleController extends Controller {
	public function getriddles(Request $request) {
		$user = Auth::user();
		if ($request->has('ids')) {
			$riddles = DB::table('riddles')
				->join('users', 'riddles.user_id', '=', 'users.id')
				->select('riddles.id', 'riddles.answer', 'riddles.user_id', 'riddles.riddle', 'riddles.created_at', 'riddles.updated_at', 'users.username')
				->whereIn('riddles.id', $request->input('ids'))
				->get();
		}
		else if ($request->has('random')) {
			$qty=5;
			if ($request->has('qty')) {
				$qty = intval($request->input('qty'));
			}
			$output['total'] = DB::table('riddles')->count();
			$riddles = DB::table('riddles')
				->join('users', 'riddles.user_id', '=', 'users.id')
				->select('riddles.id', 'riddles.answer', 'riddles.user_id', 'riddles.riddle', 'riddles.created_at', 'riddles.updated_at', 'users.username')
				->take($qty)
				->inRandomOrder()
				->get();
		}
		else if ($request->has('sort')) {
			//todo
		}

		foreach ($riddles as $riddle) {
			//TODO this will become taxing as popularity increases, consider adding 'upvotes' and 'donwvotes'
			//columns to the riddles table which are updated every x minutes or some interval (maybe instantly with each vote)
			$riddle->upvotes = DB::table('riddle_votes')->where('riddle_id', $riddle->id)->where('vote', 1)->count();
			$riddle->downvotes = DB::table('riddle_votes')->where('riddle_id', $riddle->id)->where('vote', 0)->count();
			
			if ($user) {	
				$r = DB::table('riddle_votes')->select('vote')->where('riddle_id', $id)->where('user_id', $user->id)->first();
				$riddle->voted=$r->vote;
			}
			else { $riddle->voted=-1; }
		}

		$output['riddles']=$riddles;
		return response()->json($output);
	}

	public function getRiddleDetails(Request $request, $id) {
		$riddle_id = intval($id);
		$riddle = DB::table('riddles')
		->join('users', 'riddles.user_id', '=', 'users.id')
			->select('riddles.*', 'users.username')
			->where('riddles.id', $riddle_id)
			->first();

		if (is_null($riddle)) {
			return response()->json(['error'=>'invalid_parameters'], 400);
		}

		$riddle->upvotes = DB::table('riddle_votes')->where('riddle_id', $riddle_id)->where('vote', 1)->count();
		$riddle->downvotes = DB::table('riddle_votes')->where('riddle_id', $riddle_id)->where('vote', 0)->count();
		
		//Check if user is logged in, if so check if they've voted
		try { 
			$user = JWTAuth::toUser(JWTAuth::getToken());
			if ($user) {
				$r = DB::table('riddle_votes')->select('vote')->where('riddle_id', $riddle_id)->where('user_id', $user->id)->first();
				if ($r) { $riddle->voted=$r->vote; }
				else { $riddle->voted=-1; }
			}
			else { $riddle->voted=-1; }
		}
		catch(JWTException $e) {
			$riddle->voted=-1;
		}

		$comments = DB::table('riddle_comments')
			->join('users', 'riddle_comments.user_id', '=', 'users.id')
			->select('riddle_comments.*', 'users.username')
			->where('riddle_comments.riddle_id', $riddle_id)
			->take(20)->get();

		$output['riddle']=$riddle;
		$output['comments']=$comments;
		
		return response()->json($output);
	}

	public function submitRiddle(Request $request) {
		$user = Auth::user();

		if (!$request->has('riddle_body') || !$request->has('riddle_answer')) {
			return response()->json(['error'=>'invalid_parameters'], 400);
		}
		$riddle = new Riddle;
		$riddle->user_id=$user->id;
		$riddle->answer=$request->input('riddle_answer');
		$riddle->riddle=$request->input('riddle_body');

		if ($riddle->save()) {
			$vote = new RiddleVote;
			$vote->user_id=$user->id;
			$vote->riddle_id=$riddle->id;
			$vote->vote=1;
			$vote->save();
			
			return response()->json(['success'=>'riddle_created', 'id'=>$riddle->id]);			
		}
		return response()->json(['error'=>'db_error'], 500);
	}
}