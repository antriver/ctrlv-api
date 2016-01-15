<?php

namespace CtrlV\Models;

use Auth;
use Config;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * CtrlV\Models\User
 *
 * @property integer                                                             $userId
 * @property string                                                              $username
 * @property integer                                                             $facebookId
 * @property string                                                              $email
 * @property string                                                              $password
 * @property \Carbon\Carbon                                                      $premiumUntil
 * @property boolean                                                             $moderator
 * @property boolean                                                             $defaultAnonymous
 * @property string                                                              $defaultPassword
 * @property \Carbon\Carbon                                                      $createdAt
 * @property \Carbon\Carbon                                                      $updatedAt
 * @property-read \Illuminate\Database\Eloquent\Collection|\CtrlV\Models\Album[] $albums
 * @property-read \Illuminate\Database\Eloquent\Collection|\CtrlV\Models\Image[] $images
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereUsername($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereFacebookId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User wherePremiumUntil($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereModerator($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereDefaultAnonymous($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereDefaultPassword($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\User whereUpdatedAt($value)
 */
class User extends Base\BaseModel implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable;
    use CanResetPassword;

    /**
     * The database fields that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'userId' => 'int',
        'facebookId' => 'int',
        'moderator' => 'boolean',
        'defaultAnonymous' => 'boolean',
    ];

    /**
     * The database fields that should be casted to DateTime/Carbon objects.
     *
     * @var array
     */
    protected $dates = [
        'premiumUntil',
    ];

    /**
     * The fields that are not output in JSON.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'facebookId',
        'defaultPassword',
    ];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    public $primaryKey = 'userId';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    public function getUrl()
    {
        return Config::get('app.user_url').strtolower($this->username);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function albums()
    {
        return $this->hasMany('\CtrlV\Models\Album', 'userId', 'userId')->orderBy('title', 'ASC');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany('\CtrlV\Models\Image', 'userId', 'userId');
    }

    /**
     * Return the properties in an array.
     *
     * @param bool $authenticated If this is the current logged in user we return more info.
     *
     * @return array
     */
    public function toArray($authenticated = false)
    {
        $array = parent::toArray();

        $array['url'] = $this->getUrl();

        // Is the current user?
        if (Auth::check() && Auth::user()->userId == $this->userId) {
            // Any extra info for the account owner
            $array['defaultPassword'] = !!$this->defaultPassword;
        } else {
            unset($array['email']);
            unset($array['defaultAnonymous']);
            unset($array['moderator']);
            unset($array['updatedAt']);
        }

        ksort($array);

        return $array;
    }
}
