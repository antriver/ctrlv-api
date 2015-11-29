<?php

namespace CtrlV\Http\Controllers;

use CtrlV\Models\Album;
use Illuminate\Http\Response;

class AlbumImagesController extends Base\ApiController
{
    /**
     * @api {get} /albums/{albumId}/images Get Album Images
     * @apiGroup Albums
     * @apiDescription Gets images in the specified album. The results are paginated with 20 results per page.
     * @apiParam {int} [page=1] Results page number.
     *
     * @param Album $album
     *
     * @return Response
     */
    public function index(Album $album)
    {
        $this->requireViewableModel($album);

        $this->validate(
            $this->request,
            [
                'page' => 'int|min:1'
            ]
        );

        $results = $album->images()->limit(10)->with('imageFile')->with('thumbnailImageFile');

        /*if (!$this->isCurrentUser($album)) {
            $results->where('anonymous', 0)->whereNull('password');
        }*/

        $results->orderBy('imageId', 'DESC');

        $paginator = $results->paginate($this->resultsPerPage);

        return $this->response(
            $this->paginatorToArray($paginator, 'images')
        );
    }
}
