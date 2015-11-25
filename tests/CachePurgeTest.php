<?php

namespace CtrlVTests;

use Config;
use CtrlV\Libraries\PictureFactory;
use CtrlV\Libraries\FileManager;
use CtrlV\Libraries\CacheManager;

class CachePurgeTest extends TestCase
{
    private $dataDir;

    private $cloudflareDataUrl;
    private $directDataUrl;

    private $s3Bucket;
    private $s3Region;
    private $s3Url;

    /**
     * @var PictureFactory
     */
    private $imageFactory;

    /**
     * @var FileManager
     */
    private $fileRepository;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    public function setUp()
    {
        parent::setUp();

        $this->dataDir = Config::get('app.data_dir');

        $this->cloudflareDataUrl = Config::get('app.data_url');
        $this->directDataUrl = Config::get('app.direct_data_url');

        $this->s3Bucket = Config::get('aws.s3.image_bucket');
        $this->s3Region = Config::get('aws.region');
        $this->s3Url = 'http://s3'
            . ($this->s3Region !== 'us-east-1' ? '.' . $this->s3Region : '')
            . '.amazonaws.com/'
            . $this->s3Bucket
            . '/';

        $this->imageFactory = new PictureFactory();
        $this->fileRepository = new FileManager();
        $this->cacheManager = new CacheManager();
    }

    public function tearDown()
    {
        unset($this->imageFactory);
        unset($this->fileRepository);
    }

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testSaveAndDeleteImage()
    {
        $image = $this->getImage();
        $filename = 'tests/' . $this->fileRepository->savePicture($image, 'tests');

        $path = $this->dataDir . $filename;
        $s3Url =  $this->s3Url . $filename;
        $cloudflareUrl = $this->cloudflareDataUrl . $filename;
        $directUrl = $this->directDataUrl . $filename;

        $this->assertFileExists($path);

        $this->assertEquals(200, $this->getHttpResponseCode($s3Url), $s3Url);

        $this->assertEquals(200, $this->getHttpResponseCode($directUrl), $directUrl);

        $this->assertEquals(200, $this->getHttpResponseCode($cloudflareUrl), $cloudflareUrl);

        $this->fileRepository->deleteFile($filename);

        $this->assertFileNotExists($path);

        $this->assertNotEquals(200, $this->getHttpResponseCode($s3Url), $s3Url);

        $this->assertNotEquals(200, $this->getHttpResponseCode($directUrl), $directUrl);

        $this->assertNotEquals(200, $this->getHttpResponseCode($cloudflareUrl), $cloudflareUrl);
    }

    public function testNginxCachePurge()
    {
        $image = $this->getImage();
        $filename = 'tests/' . $this->fileRepository->savePicture($image, 'tests');

        $path = $this->dataDir . $filename;
        $s3Url =  $this->s3Url . $filename;
        $cloudflareUrl = $this->cloudflareDataUrl . $filename;
        $directUrl = $this->directDataUrl . $filename;

        // Test that it is first served by nginx from the local filesystem
        $headers = $this->getHttpHeaders($directUrl);
        $this->assertNotContains('x-amz-request-id', $headers);
        $this->assertNotContains('X-Cache-Status', $headers);

        // Delete from local filesystem
        unlink($path);

        // Test that it is served from AWS via Nginx
        $headers = $this->getHttpHeaders($directUrl);
        $this->assertContains('x-amz-request-id', $headers);
        $this->assertContains('X-Cache-Status: MISS', $headers);

        sleep(1);

        // Test that it is cached by Nginx now
        $headers = $this->getHttpHeaders($directUrl);
        $this->assertContains('x-amz-request-id', $headers);
        $this->assertContains('X-Cache-Status: HIT', $headers);

        // Delete from remote
        $this->fileRepository->deleteFromRemote($filename);

        $this->assertNotEquals(200, $this->getHttpResponseCode($s3Url), $s3Url);

        // Test that it is still cached by Nginx
        $headers = $this->getHttpHeaders($directUrl);
        $this->assertContains('HTTP/1.1 200', $headers);
        $this->assertContains('X-Cache-Status: HIT', $headers);

        // Purge nginx cache
        $this->assertEquals(true, $this->cacheManager->purgeNginx($directUrl));

        // Test it's no longer being served
        $this->assertNotEquals(200, $this->getHttpResponseCode($directUrl), $directUrl);

        $this->fileRepository->deleteFile($filename);
    }

    /**
     * Get status code for a URL.
     *
     * @param  string $url
     * @return int
     */
    private function getHttpResponseCode($url)
    {
        sleep(1);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $httpCode;
    }

    private function getHttpHeaders($url)
    {
        sleep(1);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        $headers = curl_exec($curl);
        curl_close($curl);
        return $headers;
    }

    /**
     * @return \Intervention\Image\Image
     */
    private function getImage()
    {
        $base64 = base64_encode(file_get_contents(__DIR__ . '/assets/puppy.jpg'));
        return $this->imageFactory->createFromBase64String($base64);
    }
}
