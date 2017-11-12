<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Hook;
use App\HookVote;
use DB;
use Auth;

class HookController extends Controller {
	public function getHooks(Request $request) {
		$hooks = DB::table('hooks')->join('users', 'hooks.user_id', '=', 'users.id')->select('hooks.id', 'hooks.title', 'hooks.user_id', 'hooks.description', 'hooks.created_at', 'hooks.updated_at', 'users.username')->take(10)->inRandomOrder()->get();
		$user = Auth::user();
		foreach ($hooks as $hook) {
			//TODO this will become taxing as popularity increases, consider adding 'upvotes' and 'donwvotes'
			//columns to the hooks table which are updated every x minutes or some interval (maybe instantly with each vote)
			$hook->upvotes = DB::table('hook_votes')->where('hook_id', $hook->id)->where('vote', 1)->count();
			$hook->downvotes = DB::table('hook_votes')->where('hook_id', $hook->id)->where('vote', 0)->count();
			
			if ($user) {	
				$h = DB::table('hook_votes')->select('vote')->where('hook_id', $id)->where('user_id', $user->id)->first();
				$hook->voted=$h->vote;
			}
			else { $hook->voted=-1; }
		}

		return response()->json($hooks);
	}

	public function getHookDetails(Request $request, $id) {
		$hook_id = intval($id);
		$hook = DB::table('hooks')
		->join('users', 'hooks.user_id', '=', 'users.id')
			->select('hooks.*', 'users.username')
			->where('hooks.id', $hook_id)
			->first();

		if (is_null($hook)) {
			return response()->json(['error'=>'invalid_parameters'], 400);
		}

		$hook->upvotes = DB::table('hook_votes')->where('hook_id', $hook_id)->where('vote', 1)->count();
		$hook->downvotes = DB::table('hook_votes')->where('hook_id', $hook_id)->where('vote', 0)->count();

		if ($user = Auth::user()) {
			$h = DB::table('hook_votes')->select('vote')->where('hook_id', $hook_id)->where('user_id', $user->id)->first();
			$hook->voted=$h->vote;
		}
		else {
			$hook->voted=-1;
		}
	

		$comments = DB::table('hook_comments')
			->join('users', 'hook_comments.user_id', '=', 'users.id')
			->select('hook_comments.*', 'users.username')
			->where('hook_comments.hook_id', $hook_id)
			->take(20)->get();

		$output['hook']=$hook;
		$output['comments']=$comments;
		
		return response()->json($output);
	}

	public function submitHook(Request $request) {
		$user = Auth::user();

		if (!$request->has('hook_body') || !$request->has('hook_title')) {
			return response()->json(['error'=>'invalid_parameters'], 400);
		}
		$hook = new Hook;
		$hook->user_id=$user->id;
		$hook->title=$request->input('hook_title');
		$hook->description=$request->input('hook_body');

		if ($hook->save()) {
			$vote = new HookVote;
			$vote->user_id=$user->id;
			$vote->hook_id=$hook->id;
			$vote->vote=1;
			$vote->save();
			
			return response()->json(['success'=>'hook_created', 'id'=>$hook->id]);			
		}
		return response()->json(['error'=>'db_error'], 500);
	}
}