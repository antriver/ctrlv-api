var ImagePaster = function(url) {
    this.debug = true;
    this.url = url;
    this.pasteCatcher = null;
    this.init();
};

ImagePaster.prototype.log = function(data, extra) {
    if (this.debug) {
        console.log(data, extra);
    }
};

ImagePaster.prototype.init = function() {

    var self = this;

    /**
     * Firefox allows images to be pasted into contenteditable elements.
     * So create one of those on the page and make it have focus,
     * so paste events happen into that
     */

    // Create paste catcher element
    self.pasteCatcher = document.createElement('div');
    self.pasteCatcher.setAttribute('contenteditable', '');
    self.pasteCatcher.id = 'paste-catcher';
    document.body.appendChild(self.pasteCatcher);
    self.pasteCatcher.focus();

    // Listen for the 'paste' event on the body
    //if (browserName == 'IE') {
        //$('body').on('paste', function(event) { self.onPaste(event); });
    //} else {
        document.addEventListener('paste', function(event) {
            self.onPaste(event);
        });
    //}

    // Watch for ctrl + v being pressed
    $(document).on('keydown',function(e) {
        // 86 = v
        if (e.which == 86 && (e.ctrlKey || e.metaKey)) {
            $(document).trigger('ctrlVPressed');
        }
    });

    // Focus paste catcher when cmd or ctrlv is pressed
    $(document).on('ctrlVPressed', function(e) {
        self.pasteCatcher.focus();
    });

};

ImagePaster.prototype.onPaste = function(event) {

    var self = this;
    var done = false;
    var clipboardData;

    self.log('onPaste', JSON.stringify(event));

    if (event.clipboardData) {
        // Chrome
        clipboardData = event.clipboardData;
    } else if (window.clipboardData) {
        // IE
        clipboardData = window.clipboardData;
    } else {
        // Firefox
        //setTimeout(this.checkPasteCatcher, 1);
    }

    // IE
    if (event.msConvertURL) {
        var fileList = clipboardData.files;
        if (fileList.length > 0) {
            for (var i = 0; i < fileList.length; i++) {
                self.uploadBlob(fileList[i]);
                done = true;
            }
        }
    }

    // Check if there is something in the paste event itself
    if (clipboardData && clipboardData.items) {

        var text;
        // If there was some text on the clipboard,
        // it might be a URL. Try and paste that...
        if ((text = clipboardData.getData("text/plain"))) {
            self.uploadText(text);
            self.clearPasteCatcher();
            return;
        }

        // Loop through all the clipboardData
        for (var x = 0; x < clipboardData.items.length; x++) {
            // If this clipboard item claims to be an image, upload it
            if (clipboardData.items[x].type.indexOf('image') !== -1) {
                self.uploadBlob(clipboardData.items[x].getAsFile());
                done = true;
            }
        }
    }

    // If there was nothing found in the clipboard data
    // fall back to checking if there's anything in the editable element
    if (done) {
        self.clearPasteCatcher();
    } else {
        setTimeout(
            function() {
                self.checkPasteCatcher();
            },
            10
        );
    }
};

/**
 * Check if anything has been pasted into the paste catcher element
 * (Firefox + Safari?)
 */
ImagePaster.prototype.checkPasteCatcher = function() {

    var text = this.pasteCatcher.innerHTML;
    var child = this.pasteCatcher.childNodes[0];

    if (child) {
        if (child.tagName == 'IMG')
        {
            if (child.src.indexOf('webkit-fake-url://') != -1) {
                this.onFail('Sorry, Safari does not currently support pasting images.');
                return false;
            }
            this.uploadImage(child.src);
        } else if (child.tagName === 'A') {
            this.uploadText(child.href);
        } else {
            this.uploadText(text);
        }
    }

    this.clearPasteCatcher();
};

ImagePaster.prototype.clearPasteCatcher = function() {
    var self = this;
    setTimeout(
        function() {
            self.pasteCatcher.innerHTML = '';
        },
        1
    );
};

ImagePaster.prototype.uploadBlob = function(blob) {
    if (blob) {
        var self = this;
        var reader = new FileReader();
        reader.onload = function(evt) {
            self.uploadImage(evt.target.result);
        };
        reader.readAsDataURL(blob);
    }
};

ImagePaster.prototype.uploadText = function(text) {
    console.log('uploadText', text);
    return this.upload('text', text);

    /*if (this.validateURL(url)) {
        upload('url', url);
    } else {
        failedUpload("Can't upload that. Try pasting an image or URL.");
    }*/
};

ImagePaster.prototype.uploadImage = function(base64) {
    return this.upload('uploadImage', base64);

    /*convertImageToJpeg(base64, function(jpegBase64){
        upload('base64', jpegBase64);
    });*/
};

/*ImagePaster.prototype.convertImageToJpeg = function(base64, callback)
{
    //Convert image to a JPG so it's faster to upload
    var img = new Image();
    img.onload = function()
    {
        var canvas = convertImageToCanvas(this);
        var data = canvas.toDataURL('image/jpeg' , 0.8);
        callback(data);
    };
    img.src = base64;
};*/

/*ImagePaster.prototype.convertImageToCanvas = function(image)
{
    var canvas = document.createElement("canvas");
    canvas.width = image.width;
    canvas.height = image.height;

    var context = canvas.getContext("2d");
    context.fillStyle = "#ffffff";
    context.fillRect(0,0,image.width,image.height);
    context.drawImage(image, 0, 0);

    return canvas;
};*/

ImagePaster.prototype.upload = function(format, data) {

    var self = this;
    $(document).trigger('ctrlvuploadstart');

    $.ajax({
        url: this.url,
        timeout: 90000,
        type: 'POST',
        data:{
            format: format,
            base64: data
        },
        error: function(jqXHR, textStatus)
        {
            // Turn this error message into something useful
            var message;
            switch (textStatus) {
                case 'timeout':
                    message = 'The image took too long to upload';
                    break;
                case 'error':

                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        message = 'CtrlV server error (' +  jqXHR.responseJSON.errorType + ' ' +  jqXHR.responseJSON.message +')';
                    } else if (jqXHR.status !== 0) {
                        message = 'CtrlV server error (' + jqXHR.status + ' ' + jqXHR.statusText +')';
                    } else {
                        message = 'Can\'t connect to the CtrlV server';
                    }
                    break;
                case 'abort':
                    message = 'The upload was cancelled';
                    break;
                case 'parsererror':
                    message = 'Error uploading: couldn\'t decode the response from the server';
                    break;
                default:
                    message = textStatus;
                    break;
            }

            self.onFail(message);
        },
        success: function(res) {
            if (res.image) {
                $(document).trigger({
                    type: 'ctrlvuploadcomplete',
                    image: res.image
                });
            } else {
                self.onFail(res.message);
            }
        }
    });
};

ImagePaster.prototype.onFail = function(message) {
    $(document).trigger({
        type: 'ctrlvuploaderror',
        message: message ? message : false
    });
};

ImagePaster.prototype.validateURL = function(text) {
    var regex = new RegExp("^(http|https|ftp)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&amp;%\$#\=~_\-]+))*$");
    return regex.test(text);
};
