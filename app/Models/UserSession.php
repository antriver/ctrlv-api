<?php

namespace CtrlV\Models;

/**
 * CtrlV\Models\UserSession
 *
 * @property string $sessionKey
 * @property integer $userId
 * @property string $ip
 * @property \Carbon\Carbon $createdAt
 * @property \Carbon\Carbon $updatedAt
 * @property-read \CtrlV\Models\User $user
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereSessionKey($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereIp($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereUpdatedAt($value)
 */
class UserSession extends Base\BaseModel
{
    public $incrementing = false;

    protected $primaryKey = 'sessionKey';

    protected $guarded = [];

    protected $fillable = [
        'ip',
        'userId'
    ];

    public function user()
    {
        return $this->belongsTo('\CtrlV\Models\User', 'userId', 'id');
    }

    public function generateSessionKey()
    {
        return sha1(uniqid());
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

    public static function boot()
    {
        UserSession::creating(function (UserSession $session) {
            $session->sessionKey = $session->generateSessionKey();
        });

        parent::boot();
    }
}
