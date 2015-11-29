<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ImageFilesFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('images', function (Blueprint $table) {

            $table->integer('imageFileId')->unsigned()->nullable()->after('imageId');
            $table->foreign('imageFileId')->references('imageFileId')->on('image_files')->onDelete('set null')->onUpdate('cascade');

            $table->integer('thumbnailImageFileId')->unsigned()->nullable()->after('imageFileId');
            $table->foreign('thumbnailImageFileId')->references('imageFileId')->on('image_files')->onDelete('set null')->onUpdate('cascade');

            $table->integer('annotationImageFileId')->unsigned()->nullable()->after('thumbnailImageFileId');
            $table->foreign('annotationImageFileId')->references('imageFileId')->on('image_files')->onDelete('set null')->onUpdate('cascade');

            $table->integer('uncroppedImageFileId')->unsigned()->nullable()->after('annotationImageFileId');
            $table->foreign('uncroppedImageFileId')->references('imageFileId')->on('image_files')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropForeign('images_imagefileid_foreign');
            $table->dropColumn('imageFileId');

            $table->dropForeign('images_thumbnailfileid_foreign');
            $table->dropColumn('thumbnailFileId');

            $table->dropForeign('images_annotationimagefileid_foreign');
            $table->dropColumn('annotationImageFileId');

            $table->dropForeign('images_uncroppedfileid_foreign');
            $table->dropColumn('uncroppedFileId');
        });
    }
}
