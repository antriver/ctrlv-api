<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/assets/build/{{ Config::get('assets.version') }}/css/uploader.min.css" />
    <title>CtrlV.in Image Uploader</title>
</head>
<body>

<div id="uploader">

    <div class="upload-status" id="upload-waiting">
        <?php
            // TODO: Move this detection to ImagePaster.js
        if ($canPaste) {
            ?>
            <h3><i class="spinner-waiting"></i> Press <?=($isMac ? '<span class="btn btn-cmd">cmd &#8984;</span> + <span class="btn btn-v">V</span>' : '<span class="btn btn-ctrl">Ctrl</span> + <span class="btn btn-v">V</span>')?> to paste an image to upload.</h3>
            <?php
        }
        ?>

        {{-- <form action="/image" method="post" enctype="multipart/form-data" id="upload-alt-form">
            Or select an image: <input type="file" name="file" />
            <input type="submit" value="Go" />
        </form> --}}
    </div>

    <div class="upload-status out" id="upload-loading">
        <h3><i class="spinner-loading"></i> Uploading...</h3>
    </div>

    <div class="upload-status out" id="upload-error">
        <h3>Error</h3>
        <p></p>
    </div>

    <div class="upload-status out" id="upload-complete">
        <h3>Uploaded!</h3>
        <p>
            <!-- TODO: Start Again button should delete image -->
            <a href="#" class="btn" id="restart-btn"><i>&#10006;</i> Start Again</a> &nbsp;
            <a href="#" target="_blank" class="btn" id="view-btn">View on CtrlV.in</a> &nbsp;
            <a href="#" class="btn" id="accept-btn"><i>&#10003;</i> Use This</a>
        </p>
    </div>

</div>

<footer>Powered by <a href="http://ctrlv.in" target="_blank"><img src="/assets/img/ctrlv-white-146x60.png" alt="CtrlV.in" /></a></footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script src="/assets/build/{{ Config::get('assets.version') }}/js/uploader.min.js"></script>
<?php
if ($canPaste) {
    ?>
    <script>
    var imagePaster = new ImagePaster('/images');
    </script>
    <?php
}
?>
</body>
</html>
