<?php

use Illuminate\Database\Eloquent\Model as Eloquent;

class AcProject extends Eloquent
{

    public static $rules = [
        'name'    => 'required',
        'priority'   => 'required',//|unique:projects',
    ];

    protected $fillable = ['name', 'priority'];

    public function acUser()
    {
        return $this->belongsTo('AcUser');
    }

    public function acTasks()
    {
        return $this->hasMany('AcTask');
    }
}
