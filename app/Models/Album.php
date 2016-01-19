<?php

namespace CtrlV\Models;

use Auth;
use Config;
use Illuminate\Database\DatabaseManager;

/**
 * CtrlV\Models\Album
 *
 * @property integer                                                             $albumId
 * @property integer                                                             $userId
 * @property integer                                                             $thumbnailImageFileId
 * @property string                                                              $title
 * @property boolean                                                             $anonymous
 * @property string                                                              $password
 * @property \Carbon\Carbon                                                      $createdAt
 * @property \Carbon\Carbon                                                      $updatedAt
 * @property-read \Illuminate\Database\Eloquent\Collection|\CtrlV\Models\Image[] $images
 * @property-read \CtrlV\Models\User                                             $user
 * @property-read \CtrlV\Models\ImageFile                                        $thumbnailImageFile
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album whereAlbumId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album whereAnonymous($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\CtrlV\Models\Album whereUpdatedAt($value)
 */
class Album extends Base\BaseModel
{
    /**
     * The database fields that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'albumId' => 'int',
        'userId' => 'int',
        'anonymous' => 'bool',
    ];

    /**
     * The fields that are not output in JSON.
     *
     * @var array
     */
    protected $hidden = [
        'anonymous',
        'password',
        'pivot',
    ];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'albumId';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'albums';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany('CtrlV\Models\Image', 'albumId', 'albumId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('CtrlV\Models\User', 'userId', 'userId');
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
     * @param Image           $image
     * @param DatabaseManager $db
     *
     * @return bool
     */
    public function containsImage(Image $image, DatabaseManager $db)
    {
        return !!$db->connection()->selectOne(
            "SELECT `albumId` FROM `album_images` WHERE `albumId` = ? AND `imageId` = ?",
            [
                $this->getId(),
                $image->getId(),
            ]
        );
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array['passwordProtected'] = !!$this->password;
        $array['thumbnail'] = $this->getThumbnailImageFile();
        $array['url'] = $this->getUrl();

        // Album belongs to the current user?
        if (Auth::check() && Auth::user()->userId == $this->userId) {
            // Any extra info for the album owner
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
     * @return string
     */
    public function getUrl()
    {
        return Config::get('app.album_url').$this->albumId;
    }

    /**
     * Delete the old thumbnail image on save if changing it
     * @param array $options
     *
     * @return bool
     * @throws \Exception
     */
    public function save(array $options = [])
    {
        /**
         * Remember these IDs now because the parent::save method clears
         * the isDirty flag and the original data.
         */
        $originalThumbnailImageFileId = null;

        if ($this->isDirty('thumbnailImageFileId')) {
            $originalThumbnailImageFileId = $this->getOriginal('thumbnailImageFileId');
        }

        // Save the model
        $result = parent::save($options);

        if ($originalThumbnailImageFileId
            && $originalThumbnailImageFile = ImageFile::find($originalThumbnailImageFileId)
        ) {
            $originalThumbnailImageFile->delete();
        }

        return $result;
    }
}
