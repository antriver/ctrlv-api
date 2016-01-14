<?php

namespace CtrlV\Models;

/**
 * CtrlV\Models\ImageFileText
 *
 * @property integer                      $imageFileTextId
 * @property integer                      $imageFileId
 * @property string                       $text
 * @property \Carbon\Carbon               $createdAt
 * @property \Carbon\Carbon               $updatedAt
 * @property-read \CtrlV\Models\ImageFile $file
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFileText whereImageFileTextId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFileText whereImageFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFileText whereText($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFileText whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFileText whereUpdatedAt($value)
 */
class ImageFileText extends Base\BaseModel
{
    /**
     * The ID of this model is the same ID as the ImageFile, not auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'imageFileTextId';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'image_file_text';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function file()
    {
        return $this->belongsTo('CtrlV\Models\ImageFile', 'imageFileId', 'imageFileId');
    }
}
