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
 * @property-read \Illuminate\Database\Eloquent\Collection|\CtrlV\Models\Album[] $albums
 * @property integer $imageID
 * @property string $filename
 * @property \Carbon\Carbon $date
 * @property string $via
 * @property string $IP
 * @property integer $userID
 * @property string $key
 * @property string $uncroppedfilename
 * @property string $caption
 * @property integer $privacy
 * @property string $password
 * @property string $annotation
 * @property string $notes
 * @property boolean $thumb
 * @property integer $w
 * @property integer $h
 * @property boolean $ocr
 * @property boolean $ocrskip
 * @property string $ocrtext
 * @property boolean $ocrinprogress
 * @property boolean $tagged
 * @property integer $views
 * @property integer $filesize
 * @property string $batchID
 * @property \Carbon\Carbon $expires_at
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereImageID($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereFilename($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereDate($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereVia($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereIP($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereUserID($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereKey($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereUncroppedfilename($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereCaption($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image wherePrivacy($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereAnnotation($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereNotes($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereThumb($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereW($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereH($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereOcr($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereOcrskip($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereOcrtext($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereOcrinprogress($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereTagged($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereViews($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereFilesize($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereBatchID($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereExpiresAt($value)
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
        'userID' => 'int',
        'views' => 'int',
        'w' => 'int',
    ];

    protected $dates = [
        'date',
        'expires_at'
    ];

    protected $guarded = [];

    protected $hidden = [
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

    public $timestamps = false;

    public function albums()
    {
        return $this->belongsToMany('CtrlV\Models\Album', 'image_albums', 'imageId', 'albumId');
    }

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

                $date = new DateTime;
                $image->date = $date->format('Y-m-d H:i:s');

            }
        );
    }
}
