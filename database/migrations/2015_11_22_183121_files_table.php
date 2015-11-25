<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('fileId');

            $table->enum('directory', ['img', 'thumb', 'annotation', 'uncropped']);
            $table->string('filename', 100);
            $table->boolean('optimized')->nullable()->default(0);
            $table->boolean('copied')->nullable()->default(0);

            $table->integer('width', false, true)->nullable()->default(null);
            $table->integer('height', false, true)->nullable()->default(null);
            $table->integer('size', false, true)->nullable()->default(null);

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
        Schema::drop('files');
    }
}
