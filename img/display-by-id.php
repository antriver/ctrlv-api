<?php

require '../www/lib/ctrlvsite.php';
$site = new ctrlvsite(false);

$imageID = $_GET['imageID'];

$image = $site->getImageForDisplay($imageID, false, false);

if ($image) {

	$url = UPLOADED_IMG_URL . $image['filename'];

	// Redurect to image URL (doesn't work with RES)
	#header('Location: ' . $url);
	#die();



	// New way, using cURL
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);

	$response = curl_exec($ch);

	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header = substr($response, 0, $header_size);
	$headers = explode("\n", $header);
	array_shift($headers);
	foreach ($headers as $header) {
		if (trim($header)) {
			header($header);
		}
	}

	$body = substr($response, $header_size);

	echo $body;



	// Old way using file_get_contents
	/*$size = @getimagesize($url);
	print_r($size); die();
	$mime = image_type_to_mime_type($size[2]);
	$output = file_get_contents($url);
	header('Content-Type: '.$mime);
	echo $output;*/

} else {
	header('HTTP/1.0 404 Not Found');
}
