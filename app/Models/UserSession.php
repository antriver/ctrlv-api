<?php

namespace CtrlV\Models;

use CtrlV\Libraries\PasswordHasher;

/**
 * CtrlV\Models\UserSession
 *
 * @property string                  $sessionKey
 * @property integer                 $userId
 * @property string                  $ip
 * @property \Carbon\Carbon          $createdAt
 * @property \Carbon\Carbon          $updatedAt
 * @property-read \CtrlV\Models\User $user
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereSessionKey($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereIp($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereUpdatedAt($value)
 */
class UserSession extends Base\BaseModel
{
    /**
     * The fields that are not output in JSON.
     *
     * @var array
     */
    protected $hidden = [
        'sessionKey',
    ];

    /**
     * The primary key is a string, not auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'sessionKey';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('\CtrlV\Models\User', 'userId', 'userId');
    }

    /**
     * Generate a key to be stored in a cookie so users who are not logged in
     * can edit / delete the image.
     *
     * @param PasswordHasher $passwordHasher
     *
     * @return string
     */
    public function generateKey(PasswordHasher $passwordHasher)
    {
        $key = $passwordHasher->generateKey();

        $this->sessionKey = $key; //$passwordHasher->generateHash($key);

        return $key;
    }

    /**
     * Return the properties in an array.
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array['user'] = $this->user;

        ksort($array);

        return $array;
    }
}
