console.log("Hello world.");

var imagePaster = new ImagePaster('/image');

$(document).on('ctrlvuploadstart', function(){

    $('.upload-status').addClass('out');
    $('#upload-loading').removeClass('out');

});

$(document).on('ctrlvuploadcomplete', function(e){

    $('.upload-status').addClass('out');
    $('#upload-complete').removeClass('out');

    //$('body').append('<img style="width:100px;" src="' + e.image.urls.image + '" />');

});

$(document).on('ctrlvuploaderror', function(e){

    $('.upload-status').addClass('out');
    $('#upload-error').removeClass('out');

    $('#upload-error p').text(e.message);

});





$(document).on('keyup', function(e) {
    // Highlighting keys when pressing
    if (e.which == 91) { // cmd
        $('.kbd-cmd').removeClass('pressed');
    } else if (e.which == 17) { // ctrlv
        $('.kbd-ctrlv').removeClass('pressed');
    } else if (e.which == 86) { // v
        $('.kbd-v').removeClass('pressed');
    }
});

$(document).on('keydown', function(e) {
    // Highlighting keys when pressing
    if (e.which == 91) { // cmd
        $('.kbd-cmd').addClass('pressed');
    } else if (e.which == 17) { // ctrlv
        $('.kbd-ctrlv').addClass('pressed');
    } else if (e.which == 86) { // v
        $('.kbd-v').addClass('pressed');
    }
});
