<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Map;
use App\MapVote;
use DB;
use Auth;
use JWTAuth;
use Image;
use Tymon\JWTAuth\Exceptions\JWTException;
use Intervention\Image\Exception\NotReadableException;

//require_once(app_path().'/constants.php');

class MapController extends Controller {

	private $environments = ['Abyss/Nine Hells','Air/Sky Ship','Cave','City/Urban','Desert','Dungeon','Extraplanar','Feywild','Forest','House/Mansion','Island','Jungle','Megadungeon','Mountain','Other','Ruins','Sewer','Shadowfell','Ship','Stronghold/Castle/Tower','Swamp','Temple','Town/Village','Underdark','Underwater','Wilderness'];

	public function getMaps(Request $request) {
		if (!$request->has('envs') || !$request->has('seed') || !$request->has('page')) {
			return response()->json(['error'=>'missing_data'], 400);
		}
		$envs = explode(",", $request->input('envs'));

		$seed = $request->input('seed');
		$page = intval($request->input('page'));

		$ids = DB::table('map_environments')
				->distinct()
				->whereIn('environment', $envs)
				->take(10)
				->inRandomOrder($seed)
				->skip($page * 10)
				->pluck('map_id');
		
		$maps = DB::table('maps')
				->join('users', 'maps.user_id', '=', 'users.id')
				->select('maps.id', 'maps.user_id', 'maps.link', 'maps.title', 'maps.description', 'maps.created_at', 'maps.updated_at', 'users.username')
				->whereIn('maps.id', $ids)
				->get();
	
		$user = Auth::user();

		foreach ($maps as $map) {
			//TODO this will become taxing as popularity increases, consider adding 'upvotes' and 'donwvotes'
			//columns to the maps table which are updated every x minutes or some interval (maybe instantly with each vote)
			$map->upvotes = DB::table('map_votes')->where('map_id', $map->id)->where('vote', 1)->count();
			$map->downvotes = DB::table('map_votes')->where('map_id', $map->id)->where('vote', 0)->count();

			if ($user) {	
				$m = DB::table('map_votes')->select('vote')->where('map_id', $id)->where('user_id', $user->id)->first();
				$map->voted=$m->vote;
			}
			else { $map->voted=-1; }

			//TODO necessary?
			//$map->environments = DB::table('map_environments')->where('map_id', $map->id)->select('environment')->pluck('environment');
		}

		return response()->json($maps);
	}

	public function getMapDetails(Request $request, $id) {
		$map_id = intval($id);
		$map = DB::table('maps')
		->join('users', 'maps.user_id', '=', 'users.id')
			->select('maps.*', 'users.username')
			->where('maps.id', $map_id)
			->first();

		if (is_null($map)) {
			return response()->json(['error'=>'invalid_parameters'], 400);
		}

		$map->upvotes = DB::table('map_votes')->where('map_id', $map_id)->where('vote', 1)->count();
		$map->downvotes = DB::table('map_votes')->where('map_id', $map_id)->where('vote', 0)->count();

		//Check if user is logged in, if so check if they've voted
		try { 
			$user = JWTAuth::toUser(JWTAuth::getToken());
			if ($user) {
				$h = DB::table('map_votes')->select('vote')->where('map_id', $map_id)->where('user_id', $user->id)->first();
				if ($h) { $map->voted=$h->vote; }
				else { $map->voted=-1; }
			}
			else { $map->voted=-1; }
		}
		catch(JWTException $e) {
			$map->voted=-1;
		}

		$map->environments = DB::table('map_environments')->where('map_id', $map->id)->select('environment')->get();

		$comments = DB::table('map_comments')
			->join('users', 'map_comments.user_id', '=', 'users.id')
			->select('map_comments.*', 'users.username')
			->where('map_comments.map_id', $map_id)
			->take(20)->get();

		$output['map']=$map;
		$output['environments']=$this->environments;
		$output['comments']=$comments;
		
		return response()->json($output);
	}

	public function submitMap(Request $request) {
		$user = Auth::user();

		if (!$request->has('desc') || !$request->has('title') || !$request->has('link') || !$request->has('img') || !$request->has('envs')) {
			return response()->json(['error'=>'invalid_parameters1'], 400);
		}

		$envs = explode(",", $request->input('envs'));
		if (count($envs) < 1) {
			return response()->json(['error'=>'invalid_parameters2'], 400);
		}

		foreach ($envs as $env) {
			$env = intval($env);
			if ($env < 0 || $env >= count($this->environments)) {
				return response()->json(['error'=>'invalid_parameters3'], 400);
			}
		}

		$map = new Map;
		$map->user_id=$user->id;
		$map->link=$request->input('link');
		$map->title=$request->input('title');
		$map->description=$request->input('desc');

		if ($map->save()) {

			try {
				//TODO do this before creating Map?
				//create img
				//TODO make sure maps folder exists before using
				$image = Image::make($request->input('img'));
				$image->fit(100, 100)->save(public_path('maps/'. $map->id .'.jpg'));
			}
			catch(Exception $e) {
				$map->delete();
				return response()->json(['error'=>'img_error'], 400);
			}

			$env_ins = [];
			foreach ($envs as $env) {
				$env = intval($env);
				array_push($env_ins, ["map_id"=>$map->id, "environment"=>$env]);
			}

			DB::table('map_environments')->insert($env_ins);				

			$vote = new MapVote;
			$vote->user_id=$user->id;
			$vote->map_id=$map->id;
			$vote->vote=1;
			$vote->save();
			
			return response()->json(['success'=>'map_created', 'id'=>$map->id]);			
		}
		return response()->json(['error'=>'db_error'], 500);
	}


	public function getEnvironments(Request $request) {
		return response()->json($this->environments);
	}
}