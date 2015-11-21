<?php

namespace CtrlV\Models;

/**
 * CtrlV\Models\UserSession
 *
 * @property-read \CtrlV\Models\User $user
 * @property string $sessionKey
 * @property integer $userID
 * @property string $IP
 * @property string $date
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereSessionKey($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereUserID($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereIP($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\UserSession whereDate($value)
 */
class UserSession extends Base\BaseModel
{
    public $primaryKey = 'sessionKey';

    public function user()
    {
        return $this->belongsTo('\CtrlV\Models\User', 'userID', 'userID');
    }
}
