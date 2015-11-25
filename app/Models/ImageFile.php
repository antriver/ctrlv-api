<?php

namespace CtrlV\Models;

use App;
use Intervention\Image\Image as Picture;
use CtrlV\Libraries\PictureFactory;

/**
 * CtrlV\Models\ImageFile
 *
 * @property integer $fileId
 * @property string $directory
 * @property string $filename
 * @property boolean $optimized
 * @property boolean $copied
 * @property integer $width
 * @property integer $height
 * @property integer $size
 * @property \Carbon\Carbon $createdAt
 * @property \Carbon\Carbon $updatedAt
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereDirectory($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereFilename($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereOptimized($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereCopied($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereWidth($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereHeight($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereSize($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereUpdatedAt($value)
 */
class ImageFile extends Base\BaseModel
{
    protected $primaryKey = 'id';

    protected $table = 'files';

    public $timestamps = true;

    protected $guarded = [];

    /**
     * @return Picture|null
     */
    public function getPicture()
    {
        $fileManager = App::make('FileManager');
        return $fileManager->getPicture($this->getPath());
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->directory . '/' . $this->filename;
    }
}
