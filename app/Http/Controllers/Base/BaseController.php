<?php

namespace CtrlV\Http\Controllers\Base;

use CtrlV\Exceptions\ValidationException;
use CtrlV\Models\User;
use Illuminate\Auth\Guard;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Guard
     */
    protected $auth;

    /**
     * @var User
     */
    protected $user;

    /**
     * @param Request $request
     * @param Guard   $auth
     */
    public function __construct(Request $request, Guard $auth)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->user = $auth->user();
    }

    /**
     * Validate the given request with the given rules.
     *
     * @param \Illuminate\Http\Request $request
     * @param array                    $rules
     * @param array                    $messages
     * @param array                    $customAttributes
     *
     * @throws ValidationException
     */
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        /** @var \Illuminate\Validation\Validator $validator */
        $validator = $this->getValidationFactory()->make($request->all(), $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
