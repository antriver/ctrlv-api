<?php

namespace CtrlV\Models;

use Auth;
use Config;
use DateTime;
use Exception;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class Album extends EloquentModel
{
    protected $guarded = [];

    protected $hidden = [];

    protected $primaryKey = 'tagID';

    public $timestamps = false;

    public function images()
    {
        return $this->belongsToMany('CtrlV\Models\ImageModel', 'image_tags', 'tagID', 'imageID');
    }
}
