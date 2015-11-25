<?php

namespace CtrlV\Http\Controllers\Base;

use Auth;
use Illuminate\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Input;
use Response;
use CtrlV\Models\Image;
use CtrlV\Models\User;
use CtrlV\Models\UserSession;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class ApiController extends BaseController
{
    public function __construct(Request $request, Guard $auth)
    {
        // TODO: Add API keys and check here

        if ($sessionKey = $request->get('sessionKey')) {
            if ($session = UserSession::findOrFail($sessionKey)) {
                if ($user = User::findOrFail($session->userId)) {
                    $auth->setUser($user);
                    // Note: setUser() not login() so the user is only logged in for this request
                }
            }
        }

        parent::__construct($request);
    }

    /**
     * Create a JSON error response
     *
     * @param string $message
     * @param int $status
     *
     * @return Response
     */
    protected function error($message, $status = 400)
    {
        return new JsonResponse(
            [
                'error' => true,
                'message' => $message,
                'status' => $status
            ],
            $status
        );
    }

    /**
     * @param mixed $data
     *
     * @return Response
     */
    protected function successResponse($data)
    {
        return Response::json($data);
    }

    /**
     * Ensure that the given Image is viewable by the current visitor
     *
     * @param Image $image
     *
     * @throws HttpException
     * @return boolean
     */
    protected function requireViewableImage(Image $image)
    {
        if (!$image->isViewable($this->request->input('password'))) {
            throw new HttpException(403, "You don't have permission to view that image.");
        }
        return true;
    }

    /**
     * Ensure that the given Image is editable by the current visitor
     *
     * @param Image $image
     *
     * @throws HttpException
     * @return boolean
     */
    protected function requireEditableImage(Image $image)
    {
        if (!$image->isEditable($this->request->input('imageKey'))) {
            throw new HttpException(403, "You don't have permission to modify that image.");
        }
        return true;
    }
}
