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
        Schema::table('images', function (Blueprint $table) {
            $table->renameColumn('`imageID`', '`id`');
            $table->renameColumn('`userID`', '`userId`');
        });

        Schema::table('images', function (Blueprint $table) {
            $table->integer('`id`', true, true)->change();
            $table->integer('`userId`', false, true)->change();
            $table->timestamp('createdAt`');
            $table->timestamp('updatedAt`');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('images', function (Blueprint $table) {
            $table->renameColumn('`id`', '`imageID`');
            $table->renameColumn('`userId`', '`userID`');
            $table->dropColumn('`createdAt`');
            $table->dropColumn('`updatedAt`');
        });

        Schema::table('images', function (Blueprint $table) {
            $table->integer('`imageID`', true, false)->change();
            $table->integer('`userID`', false, false)->change();
        });
    }
}
