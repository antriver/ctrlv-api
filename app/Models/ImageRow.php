<?php

namespace CtrlV\Models;

use Auth;
use Config;
use CtrlV\Libraries\FileManager;
use Eloquence\Database\Traits\CamelCaseModel;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Intervention\Image\Image as InterventionImage;

class ImageRow extends EloquentModel
{
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

    public function toArray()
    {
        $array = parent::toArray();
        $array['urls'] = $this->getUrls();
        ksort($array);
        return $array;
    }

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
     * @param  string  $password Plain text password
     * @return boolean
     */
    public function isViewable($password = null)
    {
        if (!$this->password) {
            return true;
        }

        if (Auth::check() && $this->userID && Auth::user()->userID == $this->userID) {
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
        if (Auth::check() && $this->userID && Auth::user()->userID == $this->userID) {
            return true;
        }

        if (!empty($key) && $key == $this->key) {
            return true;
        }

        return false;
    }

    public function setImage($filename, InterventionImage $image)
    {
        $this->filename = $filename;
        $this->filesize = $image->filesize();
        $this->h = $image->height();
        $this->w = $image->width();
    }

    public function save(array $options = [])
    {
        if ($this->isDirty('filename')) {
            // Generate thumb
            // TODO
        }

        parent::save($options);
    }

    public function delete()
    {
        // TODO: Delete image files

        parent::delete();
    }
}
