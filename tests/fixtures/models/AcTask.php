<?php

use Illuminate\Database\Eloquent\Model;

class AcTask extends Model
{
    public static $rules = [];

    protected $fillable = ['name'];

    public function acProject()
    {
        return $this->belongsTo(AcProject::class);
    }
}
