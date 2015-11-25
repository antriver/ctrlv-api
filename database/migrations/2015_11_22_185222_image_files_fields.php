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
            $table->integer('fileId')->unsigned()->nullable()->after('imageId');
            $table->foreign('fileId')->references('fileId')->on('files')->onDelete('set null')->onUpdate('cascade');

            $table->integer('thumbnailFileId')->unsigned()->nullable()->after('fileId');
            $table->foreign('thumbnailFileId')->references('fileId')->on('files')->onDelete('set null')->onUpdate('cascade');

            $table->integer('annotationFileId')->unsigned()->nullable()->after('thumbnailFileId');
            $table->foreign('annotationFileId')->references('fileId')->on('files')->onDelete('set null')->onUpdate('cascade');

            $table->integer('uncroppedFileId')->unsigned()->nullable()->after('annotationFileId');
            $table->foreign('uncroppedFileId')->references('fileId')->on('files')->onDelete('set null')->onUpdate('cascade');
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
            $table->dropForeign('images_fileid_foreign');
            $table->dropColumn('fileId');

            $table->dropForeign('images_thumbnailfileid_foreign');
            $table->dropColumn('thumbnailFileId');

            $table->dropForeign('images_annotationfileid_foreign');
            $table->dropColumn('annotationFileId');

            $table->dropForeign('images_uncroppedfileid_foreign');
            $table->dropColumn('uncroppedFileId');
        });
    }
}
