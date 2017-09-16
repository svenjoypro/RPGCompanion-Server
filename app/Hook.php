<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Hook extends Model
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
     * Get the comments for the hook.
     */
    public function comments()
    {
        return $this->hasMany('App\HookComment');
    }

    /**
     * Get the votes for the hook.
     */
    public function votes()
    {
        return $this->hasMany('App\HookCommentVote');
    }
}
