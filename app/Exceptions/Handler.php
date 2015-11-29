<?php

namespace CtrlV\Exceptions;

use Exception;
use Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        HttpException::class,
    ];

    /**
     * Report or log an exception.
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Exception $e
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception $e
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();
        } else {
            if (config('app.debug')) {
                return parent::render($request, $e);
            }
            $status = 500;
        }

        $type = explode('\\', get_class($e));
        $type = array_pop($type);

        $response = [
            'error' => true,
            'errorType' => $type,
            'message' => $e->getMessage(),
            'status' => $status,
            'success' => false
        ];

        return Response::json($response, $status);
    }
}
