<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewAlbumPrivacy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `albums` CHANGE `privacy` `anonymous` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `albums` CHANGE `password` `password` VARCHAR(100)  CHARACTER SET utf8mb4  COLLATE utf8mb4_unicode_ci  NULL  DEFAULT NULL;');
        DB::statement("UPDATE albums SET anonymous = 1 WHERE anonymous = 2");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
