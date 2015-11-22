<?php

namespace CtrlV\Models;

/**
 * CtrlV\Models\Album
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\CtrlV\Models\Image[] $images
 */
class Album extends Base\BaseModel
{
    protected $guarded = [];

    protected $hidden = [];

    protected $primaryKey = 'id';
    protected $table = 'albums';

    public $timestamps = false;

    public function images()
    {
        return $this->belongsToMany('CtrlV\Models\ImageModel', 'image_albums', 'albumId', 'imageId');
    }
}
