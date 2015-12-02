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
        DB::statement("ALTER TABLE images CHANGE COLUMN `imageID` `imageId` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL");
        DB::statement("ALTER TABLE images CHANGE COLUMN `userID` `userId` INT(10) UNSIGNED NULL DEFAULT NULL");
        DB::statement("ALTER TABLE images CHANGE COLUMN `IP` `ip` VARCHAR(45) NOT NULL");
        DB::statement("ALTER TABLE images CHANGE COLUMN `via` `via` VARCHAR(20) NOT NULL");
        DB::statement("ALTER TABLE images CHANGE COLUMN `batchID` `batchId` INT(10) UNSIGNED NULL DEFAULT NULL");
        DB::statement("ALTER TABLE `images` CHANGE `key` `key` VARCHAR(255)  CHARACTER SET utf8mb4  COLLATE utf8mb4_unicode_ci  NULL  DEFAULT NULL;");
        DB::statement('ALTER TABLE `images` CHANGE `privacy` `anonymous` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `images` CHANGE `password` `password` VARCHAR(100)  CHARACTER SET utf8mb4  COLLATE utf8mb4_unicode_ci  NULL  DEFAULT NULL;');

        Schema::table('images', function (Blueprint $table) {
            $table->renameColumn('caption', 'title');
            $table->dropColumn('notes');
            $table->dropColumn('tagged');
        });

        // date -> createdAt
        DB::statement("ALTER TABLE images CHANGE COLUMN `date` `createdAt` DATETIME NULL DEFAULT NULL AFTER expiresAt");

        DB::statement("UPDATE images SET anonymous = 1 WHERE anonymous = 2");
        DB::statement("UPDATE images SET password = NULL WHERE password = ''");
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
