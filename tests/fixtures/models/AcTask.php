<?php
class AcTask extends Eloquent
{

    public static $rules = [];

    protected $fillable = ['name'];

    public function acProject()
    {
        return $this->belongsTo('AcProject');
    }

}
