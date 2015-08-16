<?php

namespace CtrlV\Libraries;

use Config;
use okw\CF\CF as CloudFlare;

class CacheManager
{
    private $dataUrl;

    public function __construct()
    {
        $this->dataUrl = Config::get('app.data_url');
    }

    public function purge($relativePath)
    {
        $url = $this->dataUrl . $relativePath;

        $nginxResult = $this->purgeNginx($url);

        try {
            $cloudFlareResult = $this->purgeCloudFlare($url);
        } catch (\okw\CF\Exception\CFException $exception) {
            $cloudFlareResult = false;
        }

        return [
            'nginx' => $nginxResult,
            'cloudflare' => $cloudFlareResult
        ];
    }

    /**
     * Purge a file from the Nginx cache.
     *
     * Nginx needs this in its config:
     *     proxy_cache_bypass $http_purge_cache;
     *
     * @param  string $relativePath
     * @return null
     */
    private function purgeNginx($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('purge-cache: 1'));

        $result = curl_exec($ch);

        return strpos($result, 'BYPASS') !== false;
    }

    private function purgeCloudFlare($url)
    {
        $cf = new CloudFlare(Config::get('services.cloudflare.email'), Config::get('services.cloudflare.key'));

        $response = $cf->zone_file_purge(array(
            'z' => Config::get('services.cloudflare.domain'),
            'url' => $url
        ));

        return $response['result'] == 'success';
    }
}