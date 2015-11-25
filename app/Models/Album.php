<?php

namespace CtrlV\Models;

/**
 * CtrlV\Models\Album
 *
 * @property integer $albumId
 * @property integer $userId
 * @property string $name
 * @property boolean $privacy
 * @property string $password
 * @property \Carbon\Carbon $createdAt
 * @property \Carbon\Carbon $updatedAt
 * @property-read \Illuminate\Database\Eloquent\Collection|\CtrlV\Models\ImageModel[] $images
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album whereAlbumId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album wherePrivacy($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album whereUpdatedAt($value)
 */
class Album extends Base\BaseModel
{
    protected $guarded = [];

    protected $hidden = [];

    protected $primaryKey = 'id';
    protected $table = 'albums';

    public $timestamps = true;

    public function images()
    {
        return $this->belongsToMany('CtrlV\Models\ImageModel', 'image_albums', 'albumId', 'imageId');
    }
}
