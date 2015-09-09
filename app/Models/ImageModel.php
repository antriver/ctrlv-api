<?php

namespace CtrlV\Models;

use Auth;
use Config;
use DateTime;
use Exception;

use CtrlV\Jobs\MakeThumbnailJob;
use CtrlV\Repositories\FileRepository;
use Eloquence\Database\Traits\CamelCaseModel;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Intervention\Image\Image;
use Illuminate\Foundation\Bus\DispatchesJobs;

class ImageModel extends EloquentModel
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
        'expires'
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

    protected $primaryKey = 'imageID';

    protected $table = 'images';

    public $timestamps = false;

    public function albums()
    {
        return $this->belongsToMany('CtrlV\Models\Albuum', 'image_tags', 'imageID', 'tagID');
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
     * @param  string  $password Plain text password
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

    public function setImageMetadata(Image $image)
    {
        $this->filesize = $image->filesize();
        $this->h = $image->height();
        $this->w = $image->width();
    }

    public function saveWithNewImage(Image $image)
    {
        $fileRepository = new FileRepository();

        if (!$filename = $fileRepository->saveImage($image)) {
            throw new Exception("Unable to store image file");
        }

        $this->setImageMetadata($image);
        $this->filename = $filename;
        return $this->save();
    }

    public function getImage()
    {
        $fileRepository = new FileRepository();
        return $fileRepository->getImage('img/' . $this->filename);
    }

    /**
     * Generate a key to be stored in a cookie so users who aren't logged in
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
        // We need to remember the original filename before saving
        // because the paren't saved method clears the dirty flag
        if ($this->isDirty('filename') || !$this->exists) {
            $this->thumb = false;
            $originalFilename = $this->getOriginal('filename');
            $makeThumb = true;
        }

        $result = parent::save($options);

        // We add the generate thumbnail job after saving to avoid any race conditions
        if ($makeThumb) {
            if ($originalFilename) {
                $fileRepository = new FileRepository();
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
        $fileRepository = new FileRepository();

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
        self::creating(function ($imageModel) {

            $imageModel->generateKey();

            $date = new DateTime;
            $imageModel->date = $date->format('Y-m-d H:i:s');

        });
    }
}
