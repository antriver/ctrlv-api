<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserFavsCleanup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('RENAME TABLE `favs` TO `user_favourite_images`');
        DB::statement('ALTER TABLE `user_favourite_images` CHANGE `userID` `userId` INT(10) UNSIGNED  NOT NULL;');
        DB::statement('ALTER TABLE `user_favourite_images` CHANGE `imageID` `imageId` INT(10) UNSIGNED  NOT NULL;');

        DB::statement('delete user_favourite_images from user_favourite_images left join users on users.userId = user_favourite_images.userId where users.userId IS NULL');
        DB::statement('delete user_favourite_images from user_favourite_images left join images on images.imageId = user_favourite_images.imageId where images.imageId IS NULL');

        Schema::table('user_favourite_images', function (Blueprint $table) {
            $table->foreign('userId')->references('userId')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('imageId')->references('imageId')->on('images')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // DB::statement('RENAME TABLE `user_favourites` TO `favs`');
    }
}
