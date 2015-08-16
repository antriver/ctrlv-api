<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use CtrlV\Factories\ImageFactory;
use CtrlV\Repositories\FileRepository;

class CachePurgeTest extends TestCase
{

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testCachePurge()
    {
        $dataUrl = Config::get('app.data_url');
        $dataDirectUrl = Config::get('app.direct_data_url');

        $s3Bucket = Config::get('aws.s3.image_bucket');
        $s3Region = Config::get('aws.region');

        $imageFactory = new ImageFactory();
        $fileRepositry = new FileRepository();

        // Create a dummy image file
        $image = $imageFactory->createFromBase64String($this->getImage());

        // Save it
        $filename = $fileRepositry->saveImage($image, $type = 'tests');

        $imagePath = '/var/www/ctrlv/img/tests/' . $filename;

        $s3Url = 'http://s3' . ($s3Region !== 'us-east-1' ? '.' . $s3Region : '') . '.amazonaws.com/' . $s3Bucket . '/tests/' . $filename;

        $cloudflareUrl = $dataUrl . 'tests/' . $filename;

        $imageDirectUrl = $dataDirectUrl . 'tests/' . $filename;

        $this->assertFileExists($imagePath);

        $this->assertEquals(200, $this->getHttpResponseCode($s3Url), $s3Url);

        $this->assertEquals(200, $this->getHttpResponseCode($imageDirectUrl), $imageDirectUrl);

        $this->assertEquals(200, $this->getHttpResponseCode($cloudflareUrl), $cloudflareUrl);


        $fileRepositry->deleteFile('tests/' . $filename);


        $this->assertFileNotExists($imagePath);

        $this->assertNotEquals(200, $this->getHttpResponseCode($s3Url), $s3Url);

        $this->assertNotEquals(200, $this->getHttpResponseCode($imageDirectUrl), $imageDirectUrl);

        $this->assertNotEquals(200, $this->getHttpResponseCode($cloudflareUrl), $cloudflareUrl);


        //$this->assertNotEmpty(file_get_contents($s3Url));

        // assert accesible via i.ctrlv.in
        //

        //
        // call delete method synchronously
        //
        // assert file deleted
        //
        // assert unloadable from i.ctrlv.in
        //
        // asset unloadable from s3
        //
        // assert unloadable from img.ctrlv.in
    }

    private function getHttpResponseCode($url)
    {
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle,  CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        return $httpCode;
    }

    private function getImage()
    {
        return base64_encode(file_get_contents(__DIR__ . '/assets/puppy.jpg'));
    }
}
