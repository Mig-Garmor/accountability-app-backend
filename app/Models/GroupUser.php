<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupUser extends Model
{
    use HasFactory;

    // Laravel assumes the table name by pluralizing the class name. If it doesn't match, set it explicitly
    protected $table = 'group_user';

    // Specify which attributes can be mass assignable
    protected $fillable = ['group_id', 'user_id', 'permission'];

    // If you don't want to use Laravel's default timestamps (created_at and updated_at)
    public $timestamps = true;

    // Define relationships if necessary. For example, to get the user of a GroupUser record
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Similarly, to get the group of a GroupUser record
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    // Add any other methods or properties needed for your application logic
}
