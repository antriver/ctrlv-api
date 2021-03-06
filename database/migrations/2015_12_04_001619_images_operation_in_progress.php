<?php

use Illuminate\Database\Migrations\Migration;

class ImagesOperationInProgress extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `images` ADD `operationInProgress` TINYINT(1)  NOT NULL  DEFAULT '0'  AFTER `batchId`");
        DB::statement("DROP VIEW `view_images`");
        DB::statement("CREATE ALGORITHM=UNDEFINED DEFINER=`ctrlv`@`localhost` SQL SECURITY DEFINER VIEW `view_images`
AS SELECT
   `i`.imageId,
   i.imageFileId,
   i.`thumbnailImageFileId`,
   i.`annotationImageFileId`,
   i.`uncroppedImageFileId`,
   i.`albumId`,
   i.`via`,
   i.`ip`,
   i.`userId`,
   i.`key`,
   i.`title`,
   CASE WHEN `i`.`albumId` IS NOT NULL THEN `a`.`anonymous` ELSE `i`.`anonymous` END AS `anonymous` ,
   CASE WHEN `i`.`albumId` IS NOT NULL THEN `a`.`password` ELSE `i`.`password` END AS `password`,
   i.`views`,
   i.`batchId`,
   i.`operationInProgress`,
   i.`expiresAt`,
   i.`createdAt`,
   i.`updatedAt`
FROM `images` AS `i`
LEFT JOIN `albums` AS `a` ON `a`.`albumId` = `i`.`albumId`");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `images` DROP `operationInProgress`");
        DB::statement("DROP VIEW `view_images`");
        DB::statement("CREATE ALGORITHM=UNDEFINED DEFINER=`ctrlv`@`localhost` SQL SECURITY DEFINER VIEW `view_images`
AS SELECT
   `i`.imageId,
   i.imageFileId,
   i.`thumbnailImageFileId`,
   i.`annotationImageFileId`,
   i.`uncroppedImageFileId`,
   i.`albumId`,
   i.`via`,
   i.`ip`,
   i.`userId`,
   i.`key`,
   i.`title`,
   CASE WHEN `i`.`albumId` IS NOT NULL THEN `a`.`anonymous` ELSE `i`.`anonymous` END AS `anonymous` ,
   CASE WHEN `i`.`albumId` IS NOT NULL THEN `a`.`password` ELSE `i`.`password` END AS `password`,
   i.`views`,
   i.`batchId`,
   i.`expiresAt`,
   i.`createdAt`,
   i.`updatedAt`
FROM `images` AS `i`
LEFT JOIN `albums` AS `a` ON `a`.`albumId` = `i`.`albumId`");
    }
}
