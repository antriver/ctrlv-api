var host;
if (window.opener && window.opener.frames) {
    /**
     * easyXDM Magic
     */
    var target = location.hash.substring(9);
    target = 'easyXDM_' + target + '_provider';
    var host = window.opener.frames[target];
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

$(document).on('click', '.restart-btn', function(e){
    e.preventDefault();
    $('.upload-status').addClass('out');
    $('#upload-waiting').removeClass('out');
    $('#container').css('background-image','');
    image = null;
});

$(document).on('click', '.accept-btn', function(e){
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
    image = e.image;
    $('#container').css('background-image','url(' + e.image.urls.image + ')');
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
