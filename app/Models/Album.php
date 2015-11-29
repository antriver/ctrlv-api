<?php

namespace CtrlV\Models;

use Auth;
use Config;
use Illuminate\Database\DatabaseManager;

/**
 * CtrlV\Models\Album
 *
 * @property integer $albumId
 * @property integer $userId
 * @property string $title
 * @property boolean $anonymous
 * @property string $password
 * @property \Carbon\Carbon $createdAt
 * @property \Carbon\Carbon $updatedAt
 * @property-read \Illuminate\Database\Eloquent\Collection|\CtrlV\Models\Image[] $images
 * @property-read \CtrlV\Models\User $user
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
        'anonymous' => 'bool'
    ];

    /**
     * The fields that are not output in JSON.
     *
     * @var array
     */
    protected $hidden = [
        'anonymous',
        'password',
        'pivot'
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
     * @return string
     */
    public function getUrl()
    {
        return Config::get('app.album_url').$this->albumId;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany('CtrlV\Models\Image', 'albumId', 'imageId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('CtrlV\Models\User', 'userId', 'userId');
    }

    /**
     * @param Image $image
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
                $image->getId()
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
}
