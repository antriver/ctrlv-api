<!DOCTYPE html>
<html>
<head>

<title>easyXDM</title>
<script type="text/javascript" src="/easyxdm/easyXDM.min.js"></script>

<script type="text/javascript">

    var popup, remoteapp;

    var proxy = new easyXDM.Rpc({
        swf: '/easyxdm/easyxdm.swf'
    }, {

        local: {
            /**
             * This is used to open up the popup. A popup with the given name should already be opened
             * in the user click handler.
             */
            open: function(name){
                remoteapp = null;
                // we now open the window, passing the name of this window (includes the channel name)
                // in case it has to look us up using the framename
                popup = window.open('/uploader' + '#uploader' + easyXDM.query.xdm_c + '', name, "width=700, height=400");
            },
            /**
             * This is where we receive the data
             * @param {Object} data The data
             */
            postMessage: function(data){
                if (popup.closed) {
                    alert("the popup has been closed - please open it again");
                    return;
                }
                var div = remoteapp.document.createElement("div");
                div.innerHTML = "data from '" + proxy.origin + "'";
                if (remoteapp) {
                    remoteapp.document.body.appendChild(div);
                    for (var key in data) {
                        if (data.hasOwnProperty(key)) {
                            div = remoteapp.document.createElement("div");
                            div.innerHTML = key + "=" + data[key];
                            remoteapp.document.body.appendChild(div);
                        }
                    }
                    remoteapp.focus();
                }
            }
        },
        remote: {
            postMessage: {}
        }
    });


    /**
     * The popup must use this window to register itself
     * @param {DOMWindow} app The window object of the popup
     */
    function setApp(app){
        remoteapp = app;
    }

    /**
     * The popup must use this method to send data. This clones the object in order to
     * work around a but in IE
     * @param {Object} data The data to send
     */
    function sendData(data){
        var copy = {};
        // copy the object in order to avoid the JSON serialization bug
        easyXDM.apply(copy, data);
        proxy.postMessage(copy);
    }

</script>
</head>
<body>
</body>
</html>
