<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'content',
        'target_user_id',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
