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
    const CREATED_AT = 'createdAt';
    const DELETED_AT = 'deletedAt';
    const UPDATED_AT = 'updatedAt';
}
