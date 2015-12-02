# CtrlV.in API

API for CtrlV.in - an image hosting site. Built in Laravel 5.

### See Also
* https://api.ctrlv.in/docs - the API documentation
* http://ctrlv.in - the website
* https://github.com/antriver/ctrlv-frontend - the website source

(URLs aren't live yet - they're still the old cruddy version)

## What's New

### v1.1
* An image being anonymous is now a separate setting from it being password protected. (i.e. You can set a password on an image and still show your name on it.) Any existing images with a password have been automatically set to anonymous as this was the previous behaviour.
* Image filenames are more unique.
* Can give an `albumId`` when uploading an image so it goes straight into an album.

## Terminology (in the code)
* **Picture**: Intervention\Image\Image object representing an actual picture.
* **Image**: Image metadata stored in the `images` table in the database.

## FIXME
* What if jobs are queued for an image that gets deleted?

## TODO
* PUT /albums/1/images/2 order=123 to reorder album images. Crud forgot about this when putting albumId on the images table...
* Image vanity URLs / non-sequential IDs
* Lock annotations. Change "uncropped" to "original". Replace uncrop/un-annotate with revert to original. Allow re-cropping.
* Batch requests for deleting images / adding to albums / removing from albums
* OCR
* API Keys
* Start again button on uploader should delete uploaded image (and/or we should have expiresAt on those images)

## Running The Queue
```bash
sudo -u www-data php artisan queue:listen --tries=3 --sleep=0 -vvv --timeout=600
```

## Generate API Doc
```bash
grunt build-api-docs
```
## Supervisor config

/etc/supervisor/conf.d/ctrlv-api-worker.conf
```
[program:ctrlv-api-worker]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/ctrlv-api
command=php artisan queue:work beanstalkd --sleep=1 --tries=2 --daemon
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/ctrlv-api-worker.log
```

```
sudo supervisorctl start ctrlv-api-worker:*
```
