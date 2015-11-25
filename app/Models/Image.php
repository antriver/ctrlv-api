<?php

namespace CtrlV\Models;

use Auth;
use Config;
use DateTime;
use Exception;
use CtrlV\Jobs\MakeThumbnailJob;
use CtrlV\Libraries\FileManager;
use Intervention\Image\Image as Picture;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * CtrlV\Models\Image
 *
 * @property integer $imageId
 * @property integer $fileId
 * @property integer $thumbnailFileId
 * @property integer $annotationFileId
 * @property integer $uncroppedFileId
 * @property string $via
 * @property string $ip
 * @property integer $userId
 * @property string $key
 * @property string $title
 * @property boolean $privacy
 * @property string $password
 * @property integer $views
 * @property integer $batchID
 * @property \Carbon\Carbon $expiresAt
 * @property \Carbon\Carbon $createdAt
 * @property \Carbon\Carbon $updatedAt
 * @property-read \Illuminate\Database\Eloquent\Collection|\CtrlV\Models\Album[] $albums
 * @property-read \CtrlV\Models\ImageFile $imageFile
 * @property-read \CtrlV\Models\ImageFile $thumbnailFile
 * @property-read \CtrlV\Models\ImageFile $uncroppedFile
 * @property-read \CtrlV\Models\ImageFile $annotationFile
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereImageId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereThumbnailFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereAnnotationFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereUncroppedFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereVia($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereIp($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereKey($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image wherePrivacy($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereViews($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereBatchID($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereExpiresAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereUpdatedAt($value)
 */
class Image extends Base\BaseModel
{
    use DispatchesJobs;

    protected $casts = [
        'filesize' => 'int',
        'h' => 'int',
        'imageID' => 'int',
        'privacy' => 'int',
        'tagged' => 'boolean',
        'thumb' => 'boolean',
        'id' => 'int',
        'views' => 'int',
        'w' => 'int',
    ];

    protected $dates = [
        'expiresAt'
    ];

    protected $guarded = [];

    protected $hidden = [
        'fileId',
        'annotationFileId',
        'thumbnailFileId',
        'uncroppedFileId',
        'annotation',
        'filename',
        'IP',
        'key',
        'notes',
        'ocr',
        'ocrinprogress',
        'ocrskip',
        'password',
        'tagged',
        'thumb',
        'uncroppedfilename',
        'via',
    ];

    protected $primaryKey = 'id';

    protected $table = 'images';

    public $timestamps = true;

    public function albums()
    {
        return $this->belongsToMany('CtrlV\Models\Album', 'image_albums', 'imageId', 'albumId');
    }

    public function imageFile()
    {
        return $this->hasOne('CtrlV\Models\ImageFile', 'id', 'fileId');
    }

    public function thumbnailFile()
    {
        return $this->hasOne('CtrlV\Models\ImageFile', 'id', 'thumbnailFileId');
    }

    public function uncroppedFile()
    {
        return $this->hasOne('CtrlV\Models\ImageFile', 'id', 'uncroppedFileId');
    }

    public function annotationFile()
    {
        return $this->hasOne('CtrlV\Models\ImageFile', 'id', 'annotationFileId');
    }

    /*public function text()
    {
        return $this->hasOneThrough('CtrlV\Models\ImageText' )
    }*/

    /**
     * Return the properties in an array.
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array['height'] = $this->h;
        $array['width'] = $this->w;
        $array['urls'] = $this->getUrls();

        $array['file'] = $this->imageFile;

        unset($array['h'], $array['w']);
        ksort($array);
        return $array;
    }

    /**
     * Return the URLs to view this image.
     *
     * @return array
     */
    public function getUrls()
    {
        return [
            'view' => Config::get('app.url') . $this->imageID,
            'image' => Config::get('app.image_url') . $this->filename,
            'thumbnail' => $this->thumb ? Config::get('app.thumbnail_url') . $this->filename : null,
            'annotation' => $this->annotation ? Config::get('app.annotation_url') . $this->annotation : null,
        ];
    }

    /**
     * Check if the image is viewable.
     * True if:
     *     - It has no password
     *     - or the logged in user is the owner of the image
     *     - or the correct password was supplied
     *
     * @param string $password Plain text password
     *
     * @return boolean
     */
    public function isViewable($password = null)
    {
        if (!$this->password) {
            return true;
        }

        if ($this->isCurrentUsersImage()) {
            return true;
        }

        // TODO: md5 ew
        if ($password && md5($password) == $this->password) {
            return true;
        }

        return false;
    }

    public function isEditable($key = null)
    {
        if ($this->isCurrentUsersImage()) {
            return true;
        }

        if (!empty($key) && $key == $this->key) {
            return true;
        }

        return false;
    }

    public function isCurrentUsersImage()
    {
        return Auth::check() && $this->userID && Auth::user()->userID == $this->userID;
    }

    public function setMetadataFromPicture(Picture $picture)
    {
        $this->filesize = $picture->filesize();
        $this->h = $picture->height();
        $this->w = $picture->width();
    }

    public function saveWithNewPicture(Picture $picture)
    {
        // FIXME: Use DI
        $fileRepository = new FileManager();

        if (!$filename = $fileRepository->savePicture($picture)) {
            throw new Exception("Unable to store image file");
        }

        $this->setMetadataFromPicture($picture);
        $this->filename = $filename;
        return $this->save();
    }

    public function getPicture()
    {
        // FIXME: Use DI
        $fileRepository = new FileManager();
        return $fileRepository->getPicture('img/' . $this->filename);
    }

    /**
     * Generate a key to be stored in a cookie so users who are not logged in
     * can edit / delete the image.
     *
     * @return string
     */
    public function generateKey()
    {
        $this->key = sha1(uniqid());
        return $this->key;
    }

    public function save(array $options = [])
    {
        $makeThumb = false;
        $originalFilename = null;
        // We need to remember the original filename before saving
        // because the parent saved method clears the dirty flag
        if ($this->isDirty('filename') || !$this->exists) {
            $this->thumb = false;
            $originalFilename = $this->getOriginal('filename');
            $makeThumb = true;
        }

        $result = parent::save($options);

        // We add the generate thumbnail job after saving to avoid any race conditions
        if ($makeThumb) {
            if ($originalFilename) {
                // FIXME: Use DI
                $fileRepository = new FileManager();
                $fileRepository->deleteFile('img/' . $originalFilename);
                $fileRepository->deleteFile('thumb/' . $originalFilename);
            }

            // Generate new thumbnail
            $this->dispatch(new MakeThumbnailJob($this));
        }

        return $result;
    }

    // TODO: What if jobs are currently processing/waiting?
    public function delete()
    {
        $fileRepository = new FileManager();

        $fileRepository->deleteFile('img/' . $this->filename);

        if ($this->thumb) {
            $fileRepository->deleteFile('thumb/' . $this->filename);
        }

        if ($this->annotation) {
            $fileRepository->deleteFile('annotation/' . $this->annotation);
        }

        if ($this->uncroppedfilename) {
            $fileRepository->deleteFile('uncropped/' . $this->uncroppedfilename);
        }

        unset($fileRepository);

        return parent::delete();
    }

    public static function boot()
    {
        parent::boot();

        // Attach event handler, on deleting of the user
        self::creating(
            function (Image $image) {

                $image->generateKey();

            }
        );
    }
}
