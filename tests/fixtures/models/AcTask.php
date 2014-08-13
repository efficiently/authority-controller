<?php

use Illuminate\Database\Eloquent\Model as Eloquent;

class AcTask extends Eloquent
{

    public static $rules = [];

    protected $fillable = ['name'];

    public function acProject()
    {
        return $this->belongsTo('AcProject');
    }
}
