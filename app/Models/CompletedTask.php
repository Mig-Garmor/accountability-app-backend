<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompletedTask extends Model
{
    protected $fillable = ['task_id', 'day'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
