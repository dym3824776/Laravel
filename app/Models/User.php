<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

	public static function boot()
	{
		parent::boot();
		static::creating(function ($user){
			$user->activation_token = str_random(30);
		});
	}

    public function gravatar($size = '100')
    {
        $hash = md5(strtolower(trim($this->attributes['email'])));
        return "http://www.gravatar.com/avatar/$hash?s=$size";
    }

    public function sendPasswordResetNotification($token)
    {
    	$this->notify(new ResetPassword($token));
    }

	public function statuses() {
		return $this->hasMany(Status::class);
    }

    public function feed() {
		return $this->statuses()->orderBy('created_at','desc');
    }

    public function followers() {
		return $this->belongsToMany(User::class,'followers','user_id','follower_id');
    }


    public function followings() {
		return $this->belongsToMany(User::class,'followers','follower_id','user_id');
    }


	public function follow($user_ids)
	{
		if (!is_array($user_ids)) {
			$user_ids = compact('user_ids');
		}
		$this->followings()->sync($user_ids, false);
	}


    public function unfollow($user_ids) {
		if(!is_array($user_ids)) {
			$user_ids = compact('user_ids');
		}
		return $this->followings()->detach($user_ids);
    }

	public function isFollowing($user_id)
	{
		return $this->followings->contains($user_id);
	}
}
