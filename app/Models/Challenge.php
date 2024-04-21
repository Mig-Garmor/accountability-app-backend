<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;

    protected $fillable = ['group_id', 'start_date'];

    // Add custom dates
    protected $dates = ['start_date'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'challenge_user');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
