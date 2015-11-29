<?php

namespace CtrlV\Libraries;

use Config;
use Exception;
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
        $url = $this->dataUrl.$relativePath;

        $nginxResult = $this->purgeNginx($url);

        try {
            $cloudFlareResult = $this->purgeCloudFlare($url);
        } catch (Exception $exception) {
            $cloudFlareResult = false;
        }

        return [
            'nginx' => $nginxResult,
            'cloudflare' => $cloudFlareResult
        ];
    }

    /**
     * Purge a file from the Nginx cache.
     * Nginx needs this in its config:
     *     proxy_cache_bypass $http_purge_cache;
     *
     * @param string $url
     *
     * @return null
     */
    public function purgeNginx($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('purge-cache: 1'));
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);

        $result = curl_exec($ch);

        return strpos($result, 'BYPASS') !== false || strpos($result, 'HTTP/1.1 40') !== false;
    }

    public function purgeCloudFlare($url)
    {
        $cf = new CloudFlare(Config::get('services.cloudflare.email'), Config::get('services.cloudflare.key'));

        $response = $cf->zone_file_purge(
            array(
                'z' => Config::get('services.cloudflare.domain'),
                'url' => $url
            )
        );

        return $response['result'] == 'success';
    }
}
