<?php

namespace CtrlV\Models;

use Config;
use CtrlV\Jobs\DeleteFileJob;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * CtrlV\Models\ImageFile
 *
 * @property integer                          $imageFileId
 * @property integer                          $originalImageFileId
 * @property string                           $directory
 * @property string                           $filename
 * @property boolean                          $optimized
 * @property boolean                          $copied
 * @property integer                          $width
 * @property integer                          $height
 * @property integer                          $size
 * @property integer                          $optimizedSize
 * @property \Carbon\Carbon                   $createdAt
 * @property \Carbon\Carbon                   $updatedAt
 * @property-read \CtrlV\Models\ImageFileText $text
 * @property-read \CtrlV\Models\ImageFileText $originalImageFile
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereImageFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereOriginalImageFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereDirectory($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereFilename($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereOptimized($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereCopied($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereWidth($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereHeight($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereSize($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereOptimizedSize($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\ImageFile whereUpdatedAt($value)
 */
class ImageFile extends Base\BaseModel
{
    use DispatchesJobs;

    /**
     * The database fields that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'imageFileId' => 'int',
        'optimized' => 'bool',
        'copied' => 'bool',
        'width' => 'int',
        'height' => 'int',
        'size' => 'int',
        'optimizedSize' => 'int',
        'originalImageFileId' => 'int',
    ];

    /**
     * The fields that are not output in JSON.
     *
     * @var array
     */
    protected $hidden = [
        'directory',
        'filename',
        'copied',
        'createdAt',
        'updatedAt',
        'imageFileId',
        'originalImageFileId',
        'optimized',
        'optimizedSize',
    ];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'imageFileId';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'image_files';

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->directory.'/'.$this->filename;
    }

    public function getAbsolutePath()
    {
        return Config::get('app.data_dir').$this->directory.'/'.$this->filename;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Returns a URL to view the file.
     *
     * @return string
     */
    public function getUrl()
    {
        return Config::get('app.data_url').$this->getPath();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function text()
    {
        return $this->hasOne('CtrlV\Models\ImageFileText', 'imageFileId', 'imageFileId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function originalImageFile()
    {
        return $this->hasOne('CtrlV\Models\ImageFileText', 'originalImageFileId', 'imageFileId');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array['url'] = $this->getUrl();
        $array['size'] = $this->optimizedSize ? $this->optimizedSize : $this->size;

        ksort($array);

        return $array;
    }

    /**
     * When deleting an ImageFile also delete the actual file it represents in the file system.
     * The relation on the database will take care of setting references to this image file to null and
     * deleting image_file_text entries.
     *
     * @param bool $deleteFiles
     *
     * @throws \Exception
     * @return bool|null
     */
    public function delete($deleteFiles = true)
    {
        if ($deleteFiles) {
            $this->dispatch(new DeleteFileJob($this->getPath()));
        }

        // FIXME: What if jobs are currently processing/waiting?
        // TODO: Delete thumbnail here?
        // TODO: Delete annotation here?

        return parent::delete();
    }
}
