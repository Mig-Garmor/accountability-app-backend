<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeUser extends Model
{
    use HasFactory;

    // Disable timestamps if your pivot table doesn't have them
    public $timestamps = false;

    // The table associated with the model.
    protected $table = 'challenge_user';

    // The attributes that are mass assignable.
    protected $fillable = ['user_id', 'challenge_id'];

    // Define relationships if needed
    // For example, a ChallengeUser might belong to a User and a Challenge
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }
}
