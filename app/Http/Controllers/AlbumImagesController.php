<?php

namespace CtrlV\Http\Controllers;

use CtrlV\Models\Album;
use Illuminate\Http\Response;

class AlbumImagesController extends Base\ApiController
{
    /**
     * @api            {get} /albums/{albumId}/images Get Album Images
     * @apiGroup       Albums
     * @apiDescription Gets images in the specified album. The results are paginated with 15 results per page.
     * @apiParam {int} [page=1] Results page number.
     *
     * @param Album $album
     *
     * @return Response
     */
    public function index(Album $album)
    {
        $this->requireViewableModel($album);

        // If we've got this far then the images inside this album are viewable too, so no need for further checks.

        $this->validate(
            $this->request,
            [
                'page' => 'int|min:1',
            ]
        );

        $results = $album->images()->limit(10)->with('imageFile')->with('thumbnailImageFile');

        $results->orderBy('imageId', 'DESC');

        $paginator = $results->paginate($this->getResultsPerPage());

        return $this->response(
            $this->paginatorToArray($paginator, 'images')
        );
    }
}
