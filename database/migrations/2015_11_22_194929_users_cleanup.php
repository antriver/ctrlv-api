<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UsersCleanup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE users CHANGE COLUMN `userID` `userId` INT(10) UNSIGNED NOT NULL");

        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('premiumUntil')->nullable()->default(null)->after('password');
        });

        DB::statement("ALTER TABLE users CHANGE COLUMN `fbID` `facebookId` BIGINT NULL DEFAULT NULL");

        DB::statement("ALTER TABLE users CHANGE COLUMN `signupdate` `createdAt` DATETIME NULL DEFAULT NULL AFTER `defaultPassword`");
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
