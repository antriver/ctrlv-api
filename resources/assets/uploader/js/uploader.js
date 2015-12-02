var host;
if (window.opener && window.opener.frames.length > 0) {
    /**
     * easyXDM Magic
     */
    var target = location.hash.substring(9);
    target = 'easyXDM_' + target + '_provider';
    host = window.opener.frames[target];
    if (window.opener) {
        try {
            // test if we have access to the document
            if (window.opener.document.title) {
                host = window.opener;
            }
        } catch (xDomainError) {
            // we have an opener, but it's not on our domain,
            host = window.opener.frames[target];
        }

        if (host) {
            try {
                host.setApp(window);
            } catch (browserHostError) {
                alert("was unable to gain a reference to the iframe");
            }
        }
    }
}

/**
 * Now stuff we actually understand
 */
var image = null;

$(document).on('click', '#restart-btn', function(e){
    e.preventDefault();
    $('.upload-status').addClass('out');
    $('#upload-waiting').removeClass('out');
    $('#uploader').css('background-image','');
    image = null;
});

$(document).on('click', '#accept-btn', function(e){
    e.preventDefault();
    if (host) {
        host.sendData({image: image});
        setTimeout(function(){
            window.close();
        }, 500);
    }
});

$(document).on('ctrlvuploadstart', function(){
    $('.upload-status').addClass('out');
    $('#upload-loading').removeClass('out');
});

$(document).on('ctrlvuploadcomplete', function(e){
    $('.upload-status').addClass('out');
    $('#upload-complete').removeClass('out');
    var viewUrl = e.image.url;
    var imageUrl = e.image.image.url;
    $('#view-btn').attr('href', viewUrl);
    $('#uploader').css('background-image','url(' + imageUrl + ')');
});

$(document).on('ctrlvuploaderror', function(e){
    $('.upload-status').addClass('out');
    $('#upload-error').removeClass('out').find('p').text(e.message);
});

$(document).on('keyup', function(e) {
    // Highlighting keys when pressing
    if (e.which == 91 || e.which === 224) { // cmd
        $('.btn-cmd').removeClass('active');
    } else if (e.which == 17) { // ctrl
        $('.btn-ctrl').removeClass('active');
    } else if (e.which == 86) { // v
        $('.btn-v').removeClass('active');
    }
});

$(document).on('keydown', function(e) {

    console.log(e);

    // Highlighting keys when pressing
    if (e.which == 91 || e.which === 224) { // cmd (91 in
        $('.btn-cmd').addClass('active');
    } else if (e.which == 17) { // ctrl
        $('.btn-ctrl').addClass('active');
    } else if (e.which == 86) { // v
        $('.btn-v').addClass('active');
    }
});
