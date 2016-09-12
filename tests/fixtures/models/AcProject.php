<?php

use Illuminate\Database\Eloquent\Model;

class AcProject extends Model
{
    public static $rules = [
        'name'     => 'required',
        'priority' => 'required',//|unique:projects',
    ];

    protected $fillable = ['name', 'priority'];

    public function acUser()
    {
        return $this->belongsTo(AcUser::class);
    }

    public function acTasks()
    {
        return $this->hasMany(AcTask::class);
    }
}
