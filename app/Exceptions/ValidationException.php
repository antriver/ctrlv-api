<?php

namespace CtrlV\Exceptions;

use Illuminate\Validation\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ValidationException extends HttpException
{
    public function __construct(Validator $validator)
    {
        $errors = '';
        foreach ($validator->errors()->getMessages() as $key => $messages) {
            $errors .= implode(' ', $messages).' ';
        }

        parent::__construct(422, trim($errors));
    }
}
