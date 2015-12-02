# CtrlV API

## What's New
* An image being anonymous is now a separate setting from it being password protected. (i.e. You can set a password on an image and still show your name on it.) Any existing images with a password have been automatically set to anonymous as this was the previous behaviour.
* Image filenames are more unique.

## Terminology
*Picture: Intervention\Image\Image object.
*Image: Image metadata stored in the database.

## FIXME
* Race conditions when generating/saving/optimizing and deleting image files?

## TODO
* Set albumId on upload
* PUT /albums/1/images/2 order=123 to reorder album images
* Album privacy
* Image vanity URLs / non-sequential IDs
* Lock annotations. Change "uncropped" to "original". Replace uncrop/un-annotate with revert to original. Allow re-cropping.
* Batch requests for deleting images / adding to albums / removing from albums
* OCR
* API Keys
* Start again button on uploader should delete uploaded image (and/or we should have expiresAt on those images)

## Commands

### Running The Queue
```bash
sudo -u www-data php artisan queue:listen --tries=3 --sleep=0 -vvv --timeout=600
```

### Generate API Doc
```bash
grunt build-api-docs
```

### Supervisor config

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


## Tesseract Setup
```
wget http://www.leptonica.com/source/leptonica-1.72.tar.gz
tar xvf leptonica-1.72.tar.gz
cd leptonica-1.72
./configure
make
make install
cd ..
wget https://github.com/tesseract-ocr/tesseract/archive/3.04.00.tar.gz
tar -xvf 3.04.00.tar.gz
cd tesseract-3.04.00/
./configure
make
make install
