<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
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
     * Get the comments for the map.
     */
    public function comments()
    {
        return $this->hasMany('App\MapComment');
    }

    /**
     * Get the votes for the map.
     */
    public function votes()
    {
        return $this->hasMany('App\MapCommentVote');
    }
}
