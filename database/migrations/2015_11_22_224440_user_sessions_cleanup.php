<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserSessionsCleanup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE user_sessions CHANGE COLUMN `IP` `ip` VARCHAR(45) NOT NULL');
        DB::statement('ALTER TABLE user_sessions CHANGE COLUMN `userID` `userId` INT(10) UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE user_sessions CHANGE COLUMN `date` `createdAt` DATETIME NOT NULL');

        DB::statement('ALTER TABLE `user_sessions` ADD `updatedAt` DATETIME  NULL  DEFAULT NULL  AFTER `createdAt`;');

        DB::statement('delete user_sessions from user_sessions left join users on users.userId = user_sessions.userId where users.userId IS NULL');

        Schema::table('user_sessions', function (Blueprint $table) {
            $table->foreign('userId')->references('userId')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
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
