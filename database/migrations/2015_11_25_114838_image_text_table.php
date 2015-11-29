<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ImageTextTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('image_file_text', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('imageFileTextId');

            $table->integer('imageFileId', false, true)->nullable()->default(null);
            $table->foreign('imageFileId')->references('imageFileId')->on('image_files')->onDelete('cascade')->onUpdate('cascade');

            $table->longText('text');

            $table->dateTime('createdAt')->nullable()->default(null);
            $table->dateTime('updatedAt')->nullable()->default(null);
        });

        DB::statement('INSERT INTO image_file_text (imageFileId, text, createdAt)
        SELECT imageFileId, ocrtext, createdAt FROM images WHERE ocr = 1');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('image_file_text');
    }
}
