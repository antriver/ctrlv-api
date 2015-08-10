<?php

namespace CtrlV\Models;

use Auth;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class UserSession extends EloquentModel
{
    public $primaryKey = 'sessionKey';

    public function user()
    {
        return $this->belongsTo('\CtrlV\Models\User', 'userID', 'userID');
    }
}
