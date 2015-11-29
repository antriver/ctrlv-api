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
        DB::statement("ALTER TABLE users CHANGE COLUMN `userID` `userId` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL");

        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('premiumUntil')->nullable()->default(null)->after('password');
        });

        DB::statement("ALTER TABLE users CHANGE COLUMN `fbID` `facebookId` BIGINT NULL DEFAULT NULL");

        DB::statement("ALTER TABLE users CHANGE COLUMN `signupdate` `createdAt` DATETIME NULL DEFAULT NULL AFTER `defaultPassword`");

        DB::statement("ALTER TABLE users CHANGE COLUMN `defaultPrivacy` `defaultAnonymous` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0");
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
