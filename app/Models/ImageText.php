<?php

namespace CtrlV\Models;

/**
 * CtrlV\Models\ImageText
 *
 * @property integer $imageTextId
 * @property integer $fileId
 * @property string $text
 * @property \Carbon\Carbon $createdAt
 * @property \Carbon\Carbon $updatedAt
 * @property-read \CtrlV\Models\Image $image
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageText whereImageTextId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageText whereFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageText whereText($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageText whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageText whereUpdatedAt($value)
 */
class ImageText extends Base\BaseModel
{
    protected $primaryKey = 'imageId';

    protected $table = 'image_text';

    public $incrementing = false;

    public $timestamps = true;

    protected $guarded = [];

    public function image()
    {
        return $this->hasOne('CtrlV\Models\Image', 'id', 'imageId');
    }
}
