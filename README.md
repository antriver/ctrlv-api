# CtrlV API

## Terminology
*Image: InterventionImage objext
*ImageModel: Database model for a saved image

## Running The Queue
```bash
sudo -u www-data php artisan queue:listen --tries=3 --sleep=1 -vvv --timeout=600
```

## TODO
* Race conditions when generating/saving/optimizing and deleting image files?
* OCR
* Users
* Albums
* API Keys


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
