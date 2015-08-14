<?php

namespace CtrlV\Http\Controllers;

use App;
use Auth;
use Config;
use Exception;
use Input;
use Response;
use CtrlV\Http\Requests;
use CtrlV\Http\Controllers\Base\ApiController;
use CtrlV\Models\ImageRow;
use CtrlV\Repositories\ImageRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ImageController extends ApiController
{
    /**
     * Ensure that the given ImageRow is viewable by the current visitor
     * @param  ImageRow $imageRow
     * @throws HttpException
     * @return boolean
     */
    private function requireViewableImageRow(ImageRow $imageRow)
    {
        if (!$imageRow->isViewable(Input::get('password'))) {
            App::abort(403);
            return false;
        }
        return true;
    }

    /**
     * Ensure that the given ImageRow is editable by the current visitor
     * @param  ImageRow $imageRow
     * @throws HttpException
     * @return boolean
     */
    private function requireEditableImageRow(ImageRow $imageRow)
    {
        if (!$imageRow->isEditable(Input::get('key'))) {
            App::abort(403);
            return false;
        }
        return true;
    }

    /**
     * POST
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request, ImageRepository $imageRepository)
    {
        sleep(1); // TODO remove this

        if ($request->has('base64')) {
            $image = $imageRepository->createFromBase64String($request->input('base64'));

        } elseif ($request->hasFile('file')) {
            $image = $imageRepository->createFromUploadedFile($request->file('file'));

        } else {
            throw new HttpException(400, 'Please provide a base64 image or uploaded file');

        }

        if (empty($image)) {
            App::abort(500);
        }

        if (!$filename = $imageRepository->save($image, ImageRepository::TYPE_IMAGE)) {
            throw new Exception('Unable to save image');
        }

        $userID = Auth::check() ? Auth::user()->userID : null;

        $imageRow = new ImageRow([
            'IP' => $request->ip(),
            'userID' => $userID
        ]);
        $imageRow->setImage($filename, $image);
        $imageRow->save();

        return $this->show($imageRow->fresh());
    }

    /**
     * GET
     * Display the specified resource.
     *
     * @param  ImageRow  $imageRow
     * @return Response
     */
    public function show(ImageRow $imageRow)
    {
        $this->requireViewableImageRow($imageRow);
        return Response::json(['image' => $imageRow]);
    }

    /**
     * PUT/PATCH
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  ImageRow  $imageRow
     * @return Response
     */
    public function update(Request $request, ImageRow $imageRow)
    {
        $this->requireEditableImageRow($imageRow);

    }

    /**
     * DELETE
     * Remove the specified resource from storage.
     *
     * @param  ImageRow  $imageRow
     * @return Response
     */
    public function destroy(ImageRow $imageRow)
    {
        $this->requireEditableImageRow($imageRow);
        return Response::json(['success' => $imageRow->delete()]);
    }
}
