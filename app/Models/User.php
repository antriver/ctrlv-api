<?php

namespace CtrlV\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * CtrlV\Models\User
 *
 * @property integer $userID
 * @property string $username
 * @property string $fbID
 * @property string $email
 * @property string $password
 * @property string $signupdate
 * @property boolean $moderator
 * @property integer $defaultPrivacy
 * @property string $defaultPassword
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereUserID($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereUsername($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereFbID($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereSignupdate($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereModerator($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereDefaultPrivacy($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereDefaultPassword($value)
 */
class User extends Base\BaseModel implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    public $primaryKey = 'userID';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];
}
