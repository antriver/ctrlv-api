<?php

namespace CtrlV\Models;

/**
 * CtrlV\Models\Album
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\CtrlV\Models\ImageModel[] $images
 */
class Album extends Base\BaseModel
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
