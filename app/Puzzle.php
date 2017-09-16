<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Puzzle extends Model
{
   //protected $fillable = ['title', 'testimonial'];

	/**
	 * Get the author of the post.
	 */
	public function user()
	{
		return $this->belongsTo('App\User');
	}

	/**
     * Get the comments for the puzzle.
     */
    public function comments()
    {
        return $this->hasMany('App\PuzzleComment');
    }

    /**
     * Get the votes for the puzzle.
     */
    public function votes()
    {
        return $this->hasMany('App\PuzzleCommentVote');
    }
}
