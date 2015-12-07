<?php

namespace CtrlV\Models;

use Auth;
use Config;
use CtrlV\Jobs\MakeThumbnailJob;
use CtrlV\Libraries\PasswordHasher;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * CtrlV\Models\Image
 *
 * @property integer $imageId
 * @property integer $imageFileId
 * @property integer $thumbnailImageFileId
 * @property integer $annotationImageFileId
 * @property integer $uncroppedImageFileId
 * @property integer $albumId
 * @property string $via
 * @property string $ip
 * @property integer $userId
 * @property string $key
 * @property string $title
 * @property integer $anonymous
 * @property string $password
 * @property integer $views
 * @property integer $batchId
 * @property boolean $operationInProgress
 * @property \Carbon\Carbon $expiresAt
 * @property \Carbon\Carbon $createdAt
 * @property \Carbon\Carbon $updatedAt
 * @property-read \CtrlV\Models\ImageFile $imageFile
 * @property-read \CtrlV\Models\ImageFile $thumbnailImageFile
 * @property-read \CtrlV\Models\ImageFile $annotationImageFile
 * @property-read \CtrlV\Models\ImageFile $uncroppedImageFile
 * @property-read \CtrlV\Models\Album $album
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereImageId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereImageFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereThumbnailImageFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereAnnotationImageFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereUncroppedImageFileId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereAlbumId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereVia($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereIp($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereKey($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereAnonymous($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereViews($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereBatchId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereOperationInProgress($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereExpiresAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Image whereUpdatedAt($value)
 */
class Image extends Base\BaseModel
{
    use DispatchesJobs;

    /**
     * The database fields that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'albumId' => 'int',
        'annotationImageFileId' => 'int',
        'anonymous' => 'bool',
        'imageFileId' => 'int',
        'imageId' => 'int',
        'thumbnailImageFileId' => 'int',
        'uncroppedImageFileId' => 'int',
        'userId' => 'int',
        'views' => 'int',
    ];

    /**
     * The database fields that should be casted to DateTime/Carbon objects.
     *
     * @var array
     */
    protected $dates = [
        'expiresAt'
    ];

    /**
     * The fields that are not output in JSON.
     *
     * @var array
     */
    protected $hidden = [
        'annotationImageFile',
        'annotationImageFileId',
        'anonymous',
        'imageFile',
        'imageFileId',
        'ip',
        'key',
        'password',
        'pivot',
        'thumbnailImageFile',
        'thumbnailImageFileId',
        'uncroppedImageFile',
        'uncroppedImageFileId',
        'via'
    ];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'imageId';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'view_images';

    /**
     * imageFileId relation.
     * Use getImageFile() method instead because it's more efficient.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function imageFile()
    {
        return $this->belongsTo('CtrlV\Models\ImageFile', 'imageFileId', 'imageFileId');
    }

    /**
     * @return ImageFile|null
     */
    public function getImageFile()
    {
        return is_null($this->imageFileId) ? null : $this->imageFile;
    }

    /**
     * @param ImageFile $imageFile
     */
    public function setImageFile(ImageFile $imageFile)
    {
        $this->imageFileId = $imageFile->getId();
    }

    /**
     * thumbnailImageFileId relation.
     * Use getThumbnailImageFile() method instead because it's more efficient.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function thumbnailImageFile()
    {
        return $this->belongsTo('CtrlV\Models\ImageFile', 'thumbnailImageFileId', 'imageFileId');
    }

    /**
     * @return ImageFile|null
     */
    public function getThumbnailImageFile()
    {
        return is_null($this->thumbnailImageFileId) ? null : $this->thumbnailImageFile;
    }

    /**
     * @param ImageFile|null $imageFile
     */
    public function setThumbnailImageFile(ImageFile $imageFile = null)
    {
        if ($imageFile) {
            $this->thumbnailImageFileId = $imageFile->getId();
        } else {
            $this->thumbnailImageFileId = null;
        }
    }

    /**
     * annotationImageFileId.
     * Use getAnnotationImageFile() method instead because it's more efficient.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function annotationImageFile()
    {
        return $this->belongsTo('CtrlV\Models\ImageFile', 'annotationImageFileId', 'imageFileId');
    }

    /**
     * @return ImageFile|null
     */
    public function getAnnotationImageFile()
    {
        return is_null($this->annotationImageFileId) ? null : $this->annotationImageFile;
    }

    /**
     * @param ImageFile|null $imageFile
     */
    public function setAnnotationImageFile(ImageFile $imageFile = null)
    {
        if ($imageFile) {
            $this->annotationImageFileId = $imageFile->getId();
        } else {
            $this->annotationImageFileId = null;
        }
    }

    /**
     * Is the image cropped?
     * (Does an uncropped version exist?)
     *
     * @return bool
     */
    public function hasOriginal()
    {
        return !is_null($this->uncroppedImageFileId);
    }

    /**
     * uncroppedImageFileId relation.
     * Use getUncroppedImageFile() method instead because it's more efficient.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uncroppedImageFile()
    {
        return $this->belongsTo('CtrlV\Models\ImageFile', 'uncroppedImageFileId', 'imageFileId');
    }

    /**
     * @return ImageFile|null
     */
    public function getUncroppedImageFile()
    {
        return is_null($this->uncroppedImageFileId) ? null : $this->uncroppedImageFile;
    }

    /**
     * @param ImageFile|null $imageFile
     */
    public function setUncroppedImageFile(ImageFile $imageFile = null)
    {
        if ($imageFile) {
            $this->uncroppedImageFileId = $imageFile->getId();
        } else {
            $this->uncroppedImageFileId = null;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function album()
    {
        return $this->belongsTo('CtrlV\Models\Album', 'albumId', 'albumId');
    }

    /**
     * Return the properties in an array.
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array['annotation'] = $this->getAnnotationImageFile();
        $array['image'] = $this->getImageFile();
        $array['thumbnail'] = $this->getThumbnailImageFile();

        $array['passwordProtected'] = !!$this->password;
        $array['url'] = $this->getUrl();

        // Image belongs to the current user?
        if (Auth::check() && Auth::user()->userId == $this->userId) {
            // Any extra info for the image owner
            $array['hasOriginal'] = $this->hasOriginal();
            $array['anonymous'] = $this->anonymous;
        } else {
            if ($this->anonymous) {
                $array['userId'] = null;
            }
        }

        ksort($array);

        return $array;
    }

    /**
     * Returns the URL to the image's page on the site.
     *
     * @return string
     */
    public function getUrl()
    {
        return Config::get('app.image_url').$this->imageId;
    }

    /**
     * Since images can be uploaded without authenticating we need a way to know
     * if the request is by the person that uploaded the image. A key is generated
     * and sent back when the image is first uploaded. That should be stored by the client
     * and used in subsequent requests.
     *
     * @param PasswordHasher $passwordHasher
     *
     * @return string
     */
    public function generateKey(PasswordHasher $passwordHasher)
    {
        $key = $passwordHasher->generateKey();

        $this->key = $passwordHasher->generateHash($key);

        return $key;
    }

    /**
     * Save the Image model to the database.
     * If the fileId is changed (meaning the picture has changed) delete
     * the old file and generate a new thumbnail. Delete the old thumbnail file if one exists.
     *
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        $this->table = 'images';

        /**
         * Remember these IDs now because the parent::save method clears
         * the isDirty flag and the original data.
         */
        $generateNewThumbnail = false;
        $originalImageFileId = null;
        $originalThumbnailImageFileId = null;

        if ($this->isDirty('imageFileId') || !$this->exists) {
            $originalImageFileId = $this->getOriginal('imageFileId');
            $this->setThumbnailImageFile(null);
            $originalThumbnailImageFileId = $this->getOriginal('thumbnailImageFileId');
            $generateNewThumbnail = true;
        }

        // Save the model
        $result = parent::save($options);

        if ($originalImageFileId && $originalImageFile = ImageFile::find($originalImageFileId)) {
            if ($originalImageFileId != $this->uncroppedImageFileId) {
                $originalImageFile->delete();
            }
        }

        if ($originalThumbnailImageFileId
            && $originalThumbnailImageFile = ImageFile::find($originalThumbnailImageFileId)
        ) {
            $originalThumbnailImageFile->delete();
        }

        if ($generateNewThumbnail && $imageFile = ImageFile::find($this->imageFileId)) {
            /** @var ImageFile $imageFile */
            $this->dispatch(new MakeThumbnailJob($imageFile));
        }

        $this->table = 'view_images';

        return $result;
    }

    /**
     * Upon deleting the Image also delete the ImageFiles.
     *
     * @return bool|null
     * @throws \Exception
     */
    public function delete()
    {
        $this->table = 'images';

        if ($imageFile = $this->getImageFile()) {
            $imageFile->delete();
        }

        if ($thumbnailImageFile = $this->getThumbnailImageFile()) {
            $thumbnailImageFile->delete();
        }

        if ($annotationImageFile = $this->getAnnotationImageFile()) {
            $annotationImageFile->delete();
        }

        if ($uncroppedImageFile = $this->getUncroppedImageFile()) {
            $uncroppedImageFile->delete();
        }

        $result = parent::delete();

        $this->table = 'view_images';

        return $result;
    }
}
