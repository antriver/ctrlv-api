<?php

namespace CtrlV\Http\Controllers;

use CtrlV\Libraries\PasswordHasher;
use CtrlV\Models\Album;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @apiDefine AlbumSuccessResponse
 * @apiSuccessExample {json} Success Response
 *     {
 *         "success": true,
 *         "album": {
 *           // See Get Album Info
 *         }
 *     }
 */
class AlbumsController extends Base\ApiController
{
    /**
     * @api               {get} /albums/{albumId} Get Album Info
     * @apiGroup          Albums
     * @apiDescription    Get information about an album.
     * @apiSuccessExample Success Response
     *     {
     *       "album": {
     *         "albumId": 1,
     *         "createdAt": "2015-11-28T01:33:29+00:00",
     *         "privacy": 0,
     *         "title": "My Album",
     *         "updatedAt": "2015-11-28T01:33:29+00:00",
     *         "userId": 1
     *       }
     *     }
     *
     * @param Album $album
     *
     * @return Response
     */
    public function show(Album $album)
    {
        $this->requireViewableModel($album);

        return $this->response(['album' => $album]);
    }

    /**
     * @api            {post} /albums Create an Album
     * @apiGroup       Albums
     * @apiDescription Create a new album to group images together.
     * @apiParam {string} title Title of the album.
     * @apiUse         RequiresAuthentication
     * @apiUse         AlbumSuccessResponse
     * @return Response
     */
    public function store()
    {
        $user = $this->requireAuthentication();

        $this->validate(
            $this->request,
            [
                'title' => 'required|string|max:100|unique:albums,title,null,album_id,userId,'.$user->userId,
            ]
        );

        $album = new Album(
            [
                'title' => $this->request->input('title'),
                'userId' => $user->userId,
            ]
        );

        if ($album->save()) {
            return $this->response(
                [
                    'album' => $album->fresh(),
                    'success' => true,
                ]
            );
        }

        throw new HttpException(500, "Unable to create album.");
    }

    /**
     * @api            {put} /albums/{albumId} Update Album Info
     * @apiGroup       Albums
     * @apiDescription Update the stored metadata for an album.
     * @apiParam {string} [title] New title of the album.
     * @apiParam {boolean=0,1} [anonymous=0] Hide the name of the album owner?
     * @apiParam {string=""} [password] Password that will be needed to view the album and any images in it.
     *     Give a blank value to clear.
     *     <br/>**If an image is in an album the anonymous setting and password for the album apply instead of
     *     the images own settings.**
     * @apiUse         RequiresAuthentication
     * @apiUse         AlbumSuccessResponse
     *
     * @param Album          $album
     * @param PasswordHasher $passwordHasher
     *
     * @return Response
     */
    public function update(Album $album, PasswordHasher $passwordHasher)
    {
        $user = $this->requireAuthentication($album->userId);

        $this->validate(
            $this->request,
            [
                'title' => 'string|max:100|unique:albums,title,'.$album->albumId.',albumId,userId,'.$user->userId,
                'anonymous' => 'boolean',
                'password' => '',
            ]
        );

        if ($title = $this->request->input('name')) {
            $album->title = $title;
        }

        if ($this->request->exists('anonymous')) {
            $album->anonymous = (bool)$this->request->input('anonymous');
        }

        if ($this->request->exists('password')) {
            if ($password = $this->request->input('password')) {
                $album->password = $passwordHasher->generateHash($password);
            } else {
                $album->password = null;
            }
        }

        if ($album->save()) {
            return $this->response(
                [
                    'album' => $album->fresh(),
                    'success' => true,
                ]
            );
        }

        throw new HttpException(500, "Unable to update album.");
    }

    /**
     * @api            {delete} /albums/{albumId} Delete an Album
     * @apiGroup       Albums
     * @apiDescription Delete a album. This does not delete the images that were in it.
     * @apiUse         RequiresAuthentication
     * @apiUse         GenericSuccessResponse
     *
     * @param Album $album
     *
     * @return Response
     * @throws HttpException
     */
    public function destroy(Album $album)
    {
        $this->requireAuthentication($album->userId);

        return $this->response(['success' => $album->delete()]);
    }
}
