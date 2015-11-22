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
            $table->integer('fileId')->unsigned()->after('imageID');
            $table->foreign('fileId')->references('image_files')->on('id')->onDelete('cascade')->onUpdate('cascade');

            $table->integer('thumbnailId')->unsigned()->after('fileId');
            $table->foreign('thumbnailId')->references('image_files')->on('id')->onDelete('cascade')->onUpdate('cascade');
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
            $table->dropForeign('fileId_foreign');
            $table->dropForeign('thumbnailFileId_foreign');

            $table->dropColumn('fileId');
            $table->dropColumn('thumbnailId');
        });
    }
}
