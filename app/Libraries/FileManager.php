<?php

// TODO: This is totally not ready

namespace CtrlV\Libraries;

use Pheanstalk_PheanstalkInterface;

class FileManager
{

    private $localDir;
    public $S3;

    public function __construct()
    {
        $this->localDir = DATA_DIR;
        require __DIR__ . '/S3Manager.php';
        $this->S3 = new S3Manager(AWS_ACCESS_KEY, AWS_SECRET_KEY, S3_BUCKET_NAME, S3_ENDPOINT);
    }

    public function generateFilename($extension = '', $prefix = '')
    {
        $f = date('y/m/d') . '/';
        if ($prefix) {
            $f .= $prefix . '-';
        }
        $f .= uniqid();
        if ($extension) {
            $f .= '.' . $extension;
        }
        return $f;
    }

    public function getPathFromFilename($filename)
    {
        $path = explode('/', $filename);
        if (count($path) > 1) {
            array_pop($path);
            $path = implode('/', $path);
            return ltrim($path, '/');
        } else {
            return false;
        }
    }

    public function makeDirectoryForFile($filename)
    {
        if ($path = $this->getPathFromFilename($filename)) {
            if (!is_dir($this->localDir . $path)) {
                mkdir($this->localDir . $path, 0777, true);
            }
        }
    }

    public function save($filename, $data, $where = 3)
    {
        $success = 0;

        if ($where & 1) {
            if ($this->saveLocal($filename, $data)) {
                $success += 1;
            }
        }

        //Tell the queue to process this image
        $pheanstalk = new Pheanstalk_Pheanstalk(BEANSTALKD_SERVER);
        $payload = [
            'filename' => $filename
        ];
        $pheanstalk->useTube('ctrlv-img-process')->put(
            json_encode($payload),
            Pheanstalk_PheanstalkInterface::DEFAULT_PRIORITY,
            Pheanstalk_PheanstalkInterface::DEFAULT_DELAY,
            1800 // 30 minute TTR
        );

        /*if ($this->S3 && $where & 2) {
            //S3
            if ($this->S3->add($filename, $data, 'image/jpeg')) {
                $success += 2;
            }
        }*/

        return $success;
    }

    public function saveLocal($filename, $data)
    {
        //Make the directory if necessary
        $this->makeDirectoryForFile($filename);

        //Local
        if (file_put_contents($this->localDir . $filename, $data)) {
            return true;
        }
    }

    public function get($filename)
    {
        //Try local
        if ($data = $this->getLocal($filename)) {
            return $data;
        }

        //Try remote
        if ($data = $this->getRemote($filename)) {
            return $data;
        }

        return false;
    }

    public function localExists($filename)
    {
        return file_exists($this->localDir . $filename);
    }

    public function getLocal($filename)
    {
        if ($this->localExists($filename)) {
            $data = file_get_contents($this->localDir . $filename);
            return $data;
        }
        return null;
    }

    public function getRemote($filename)
    {
        if (!$this->S3) {
            return false;
        }
        $data = $this->S3->get($filename);
        return $data;
    }

    public function remoteToLocal($filename)
    {
        if ($this->localExists($filename)) {
            return true;
        }
        $data = $this->getRemote($filename);
        if (!$data) {
            return false;
        }
        $this->saveLocal($filename, $data);
        return $data;
    }

    public function saveRemote($filename, $data)
    {
        if ($this->S3 && $this->S3->add($filename, $data, 'image/jpeg')) {
            return true;
        }
    }

    public function localToRemote($filename)
    {
        $data = $this->getLocal($filename);
        if (!$data) {
            return false;
        }
        return $this->saveRemote($filename, $data);
    }

    public function delete($filename)
    {
        // Local
        if (file_exists($this->localDir . $filename)) {
            unlink($this->localDir . $filename);
        }

        // S3
        if ($this->S3) {
            $this->S3->delete($filename);
        }

        //Purge from Varnish cache
        #$curl = curl_init( DATA_URL.$filename );
        #curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PURGE");
        #curl_exec($curl);

        $this->purgeCaches($filename);
    }

    public function purgeCaches($filename)
    {
        #error_log('Cloudflare purge: ' . json_encode($this->purgeCloudflareCache($filename)));
        $this->purgeNginxCache($filename);
    }

    public function purgeNginxCache($filename)
    {
        //Purge from Nginx cache
        $cmd = 'curl "' . DATA_URL . $filename . '" -s -I -H "purge-cache:1" > /dev/null &';
        exec($cmd);
    }

    public function purgeCloudflareCache($filename)
    {
        return $this->getCloudflare()->zone_file_purge(CLOUDFLARE_DOMAIN, DATA_URL . $filename);
    }

    public function getCloudflare()
    {
        if (isset($this->cloudflare)) {
            return $this->cloudflare;
        }
        require dirname(__DIR__) . '/vendor/vexxhost/cloud-flare-api/class_cloudflare.php';
        $this->cloudflare = new cloudflare_api(CLOUDFLARE_USERNAME, CLOUDFLARE_KEY);
        return $this->cloudflare;
    }

    public function move($oldFilename, $newFilename)
    {
        //Local
        if ($this->localExists($oldFilename)) {
            //Make the directory if necessary
            $this->makeDirectoryForFile($newFilename);

            rename($this->localDir . $oldFilename, $this->localDir . $newFilename);
        }

        //S3
        if ($this->S3) {
            $this->S3->move($oldFilename, $newFilename);
        }
    }

    public function imageSize($filename)
    {
        //Check if we have the image locally, if we do just use getimagesize on it
        if ($this->localExists($filename)) {
            return getimagesize($this->localDir . $filename);
        } else { //Otherwise it's only remote
            //If we have a new enough PHP, just get the data and run get getimagesizefromstring on it
            if (function_exists('getimagesizefromstring')) {
                $data = $this->get($filename);
                return getimagesizefromstring($data);
            } //Otherwise we'll have to download the image to local, then run getimagesize
            else {
                if ($this->remoteToLocal($filename)) {
                    return getimagesize($this->localDir . $filename);
                }
            }
        }
    }

    public function fileSize($filename)
    {
        $data = $this->get($filename);
        if (function_exists('mb_strlen')) {
            $size = mb_strlen($data, '8bit');
        } else {
            $size = strlen($data);
        }
        return $size;
    }
}
