<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/assets/build/{{ Config::get('assets.version') }}/css/upload.min.css" />
    <title>CtrlV.in Image Uploader</title>
</head>
<body>

    <div id="container">
        <div>

            <div class="upload-status" id="upload-waiting">

                <h3><i class="spinner-pulse"></i> Press <?=($mac ? '<span class="kbd kbd-cmd">cmd &#8984;</span> + <span class="kbd kbd-v">V</span>' : '<span class="kbd kbd-ctrl">Ctrl</span> + <span class="kbd kbd-v">V</span>')?> to paste an image to upload.</h3>

                <form action="/image" method="post" enctype="multipart/form-data" id="upload-alt-form">
                    Or select an image: <input type="file" name="file" />
                </form>

            </div>

            <div class="upload-status out" id="upload-loading">
                <h3><i class="spinner-chase"></i> Uploading...</h3>
            </div>

            <div class="upload-status out" id="upload-error">
                <h3>Error</h3>
                <p></p>
            </div>

            <div class="upload-status out" id="upload-complete">
                <h3>Uploaded!</h3>
                <p><a class="btn kbd">Use this image</a> or <a class="kbd">Choose another</a></p>
            </div>

        </div>
    </div>

<footer>Powered by <a href="http://ctrlv.in" target="_blank"><img src="/assets/img/ctrlv-white-146x60.png" alt="CtrlV.in" /></a></footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script src="/assets/build/{{ Config::get('assets.version') }}/js/upload.min.js"></script>

</body>
</html>
