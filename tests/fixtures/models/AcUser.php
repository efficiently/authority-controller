<?php

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableInterface;

/**
 * An Ardent Model: 'User'
 *
 * @property integer $id
 * @property string $username
 * @property string $email
 * @property string $firstname
 * @property string $lastname
 * @property string $password
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AcUser extends Eloquent implements UserInterface, RemindableInterface
{

    public static $passwordAttributes  = ['password'];
    public $autoHashPasswordAttributes = true;

    public $autoPurgeRedundantAttributes = true;

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
        return $this->belongsToMany('Role');
    }

    public function permissions()
    {
        return $this->hasMany('Permission');
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

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the e-mail address where password reminders are sent.
     *
     * @return string
     */
    public function getReminderEmail()
    {
        return $this->email;
    }

}
