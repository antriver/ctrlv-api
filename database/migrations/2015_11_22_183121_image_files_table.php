<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ImageFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('image_files', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('imageFileId');

            $table->integer('originalImageFileId')->unsigned()->nullable();
            $table->foreign('originalImageFileId')->references('imageFileId')->on('image_files')->onDelete('set null')->onUpdate('cascade');

            $table->enum('directory', ['img', 'thumb', 'annotation', 'uncropped']);
            $table->string('filename', 100);
            $table->boolean('optimized')->nullable()->default(0);
            $table->boolean('copied')->nullable()->default(0);

            $table->integer('width', false, true)->nullable()->default(null);
            $table->integer('height', false, true)->nullable()->default(null);
            $table->bigInteger('size', false, true)->nullable()->default(null);
            $table->bigInteger('optimizedSize', false, true)->nullable()->default(null);

            $table->dateTime('createdAt')->nullable()->default(null);
            $table->dateTime('updatedAt')->nullable()->default(null);

            $table->unique(['directory', 'filename']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('image_files');
    }
}
