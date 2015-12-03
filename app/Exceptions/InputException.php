<?php

namespace CtrlV\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * InputExceptions have an additional "messages" property that says
 * which input field an error relates to.
 */
class InputException extends HttpException
{
    private $messages = [];

    public function __construct($statusCode, array $messages)
    {
        $this->messages = $messages;

        $flattenedMessages = [];
        array_walk_recursive($messages, function($a) use (&$flattenedMessages) {
            $flattenedMessages[] = $a;
        });
        $message = implode(' ', $flattenedMessages);

        parent::__construct($statusCode, $message);
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
