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
            $table->increments('id');
            $table->enum('directory', ['img', 'thumb', 'annotation', 'uncropped']);
            $table->string('filename');
            $table->boolean('optimized');
            $table->boolean('copied');
            $table->timestamp('createdAt');
            $table->timestamp('updatedAt');

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
