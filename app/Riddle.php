<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Riddle extends Model
{
    //

    /**
     * Get the author of the post.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
    
    /**
     * Get the comments for the riddle.
     */
    public function comments()
    {
        return $this->hasMany('App\RiddleComment');
    }

    /**
     * Get the votes for the riddle.
     */
    public function votes()
    {
        return $this->hasMany('App\RiddleCommentVote');
    }
}
