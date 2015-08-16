# CtrlV API

## Terminology
*Image: InterventionImage objext
*ImageModel: Database model for a saved image

## Running The Queue
```bash
sudo -u www-data php artisan queue:listen --tries=3 --sleep=1 -vvv --timeout=600
```

## TODO
* Race conditions when generating/saving/optimizing and deleting image files


## Generate API Doc
```bash
npm-exec apidoc -i app/Http/Controllers -o public/docs -t apidoc-template
```
