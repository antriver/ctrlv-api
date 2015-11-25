<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ImageCleanup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE images CHANGE COLUMN `imageID` `imageId` INT(10) UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE images CHANGE COLUMN `userID` `userId` INT(10) UNSIGNED NULL DEFAULT NULL");
        DB::statement("ALTER TABLE images CHANGE COLUMN `IP` `ip` VARCHAR(45) NOT NULL");
        DB::statement("ALTER TABLE images CHANGE COLUMN `via` `via` VARCHAR(20) NOT NULL");

        DB::statement('ALTER TABLE `images` CHANGE `password` `password` VARCHAR(100)  CHARACTER SET utf8mb4  COLLATE utf8mb4_unicode_ci  NULL  DEFAULT NULL;');

        Schema::table('images', function (Blueprint $table) {
            $table->renameColumn('caption', 'title');
            $table->dropColumn('notes');
            $table->dropColumn('tagged');
        });

        // date -> createdAt
        DB::statement("ALTER TABLE images CHANGE COLUMN `date` `createdAt` DATETIME NULL DEFAULT NULL AFTER expiresAt");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
