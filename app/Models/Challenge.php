<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;

    protected $fillable = ['group_id', 'name']; // Ensure 'name' and any other relevant fields are fillable

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'challenge_user'); // Assuming 'challenge_user' is your pivot table name
    }
}
