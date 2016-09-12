<?php

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property integer $id
 * @property string  $username
 * @property string  $email
 * @property string  $firstname
 * @property string  $lastname
 * @property string  $password
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AcUser extends Authenticatable
{
    use Notifiable;

    public static $rules = [
        'username'              => 'required|between:3,16|unique:users',
        'email'                 => 'required|email|unique:users',
        'password'              => 'required|min:4|confirmed',
        'password_confirmation' => 'required|min:4',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password'];

    protected $fillable = ['username', 'firstname', 'lastname', 'email', 'displayname'];

    public function roles()
    {
        return $this->belongsToMany(AcRole::class)->withTimestamps();
    }

    public function permissions()
    {
        return $this->hasMany(AcPermission::class);
    }

    public function hasRole($key)
    {
        foreach ($this->roles as $role) {
            if ($role->name === $key) {
                return true;
            }
        }
        return false;
    }
}
