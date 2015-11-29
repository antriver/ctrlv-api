<?php

namespace CtrlV\Models\Base;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Class BaseModel
 *
 * @mixin \Eloquent
 */
abstract class BaseModel extends EloquentModel
{
    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'createdAt';

    /**
     * The name of the "delete at" column (for soft deletes).
     *
     * @var string
     */
    const DELETED_AT = 'deletedAt';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updatedAt';

    /**
     * Indicates if all mass assignment is enabled.
     *
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * Returns the primary key.
     *
     * @return int
     */
    public function getId()
    {
        return $this->{$this->primaryKey};
    }

    /**
     * Return the attributes in an array.
     * Here we convert DateTimes to ISO 8601 format.
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        foreach ($this->getDates() as $dateAttribute) {
            if (in_array($dateAttribute, $this->hidden)) {
                continue;
            }
            $value = $this->{$dateAttribute};
            if ($value) {
                $carbon = $this->asDateTime($this->{$dateAttribute});
                $array[$dateAttribute] = $carbon->format(\DateTime::ATOM);
            }
        }

        return $array;
    }
}
