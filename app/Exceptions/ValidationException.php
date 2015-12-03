<?php

namespace CtrlV\Exceptions;

use Illuminate\Validation\Validator;

class ValidationException extends InputException
{
    public function __construct(Validator $validator)
    {
        parent::__construct(422, $validator->errors()->getMessages());
    }
}
