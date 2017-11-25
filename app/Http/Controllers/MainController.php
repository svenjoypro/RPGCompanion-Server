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

	public function getReleaseNotes(Request $request) {
		$o['msg']="Update 11/23/17 Release Notes\n\nHappy Thanksgiving!\n\nCreated a Loot Generator section. It probably needs some tweaking still so let me know what you think.\n\nAdded a link in the Encounters section that takes you to Kobold Fight Club for building encounters.  The eventual goal is to have unique finely crafted encounters, as opposed to just throwing some monsters together based on the Challenge Rating, but that will be in the future.\n\n\nUpdate: 11/20/17 Release Notes\n\nQuite a few craches recently, I'm hoping this solves the problem, although I'm not entirely sure what the problem is. Feel free to contact me with details on any crashes you have.\n\n\nUpdate: 11/16/17 Release Notes \n\nPassword resets now implemented from the login page (clicking the forgot your password link)\n\nContact the developer link put into the menu\n\nAdded 4 new d100 lists: Interesting Books, Unique Shops and Stores, Holy Pilgrimage Quests, and Dungeon Lever Consequences\n\n\n\nUpdate: 11/12/17 Release Notes \n\nYou can now create an account, which requires a username and an email address which must be confirmed.\n\nWith an account you can submit your own riddles and plot hooks as well as vote on other user's submissions.\n\nThere is no way yet to view all of your submissions, but that will be coming soon; as will sorting - there will probably be sort by date and sort by number of votes as well as the current randomize.\n\nWe've reached 1000 downloads, so thanks to all of you for the encouragement to keep with this.";
		return response()->json($o);
	}
	
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
}
