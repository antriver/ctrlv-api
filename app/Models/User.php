<?php

namespace CtrlV\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * CtrlV\Models\User
 *
 * @property integer $userId
 * @property string $username
 * @property integer $facebookId
 * @property string $email
 * @property string $password
 * @property string $premiumUntil
 * @property boolean $moderator
 * @property boolean $defaultPrivacy
 * @property string $defaultPassword
 * @property \Carbon\Carbon $createdAt
 * @property \Carbon\Carbon $updatedAt
 * @property-read \Illuminate\Database\Eloquent\Collection|\CtrlV\Models\Image[] $images
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereUsername($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereFacebookId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User wherePremiumUntil($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereModerator($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereDefaultPrivacy($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereDefaultPassword($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereUpdatedAt($value)
 */
class User extends Base\BaseModel implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    protected $casts = [
        'id' => 'int',
        'moderator' => 'boolean',
        'defaultPrivacy' => 'int',
    ];

    public $primaryKey = 'id';

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
    protected $fillable = [
        'username',
        'email',
        'password'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'fbID',
        'defaultPassword'
    ];

    /**
     * Return the properties in an array.
     *
     * @param bool $authenticated
     *
     * @return array
     */
    public function toArray($authenticated = false)
    {
        $array = parent::toArray();

        if (!$authenticated) {
            unset($array['email']);
            unset($array['defaultPrivacy']);
            unset($array['moderator']);
            unset($array['updatedAt']);
        }

        ksort($array);
        return $array;
    }

    public function images()
    {
        return $this->hasMany('\CtrlV\Models\Image', 'userId', 'id');
    }
}
