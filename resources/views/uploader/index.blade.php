<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/assets/build/{{ Config::get('assets.version') }}/css/uploader.min.css" />
    <title>CtrlV.in Image Uploader</title>
</head>
<body>

<div id="container">

    <div class="upload-status" id="upload-waiting">
        <div>
            <?php
            if ($canPaste) {
                ?>
                <h3><i class="spinner-pulse"></i> Press <?=($isMac ? '<span class="kbd kbd-cmd">cmd &#8984;</span> + <span class="kbd kbd-v">V</span>' : '<span class="kbd kbd-ctrl">Ctrl</span> + <span class="kbd kbd-v">V</span>')?> to paste an image to upload.</h3>
                <?php
            }
            ?>

            {{-- <form action="/image" method="post" enctype="multipart/form-data" id="upload-alt-form">
                Or select an image: <input type="file" name="file" />
                <input type="submit" value="Go" />
            </form> --}}
        </div>
    </div>

    <div class="upload-status out" id="upload-loading">
        <div>
            <h3><i class="spinner-chase"></i> Uploading...</h3>
        </div>
    </div>

    <div class="upload-status out" id="upload-error">
        <div>
            <h3>Error</h3>
            <p></p>
        </div>
    </div>

    <div class="upload-status out" id="upload-complete">
        <div>
            <h3>Uploaded!</h3>
            <p><a href="#" class="restart-btn kbd"><i>&#10006;</i> Start Again</a> &nbsp; or &nbsp; <a href="#" class="btn kbd accept-btn"><i>&#10003;</i> Use This</a></p>
        </div>
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
