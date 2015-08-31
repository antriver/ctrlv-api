var ctrlv = {

    callback: null,

    proxy: null,

    popup: null,

    width: 700,

    height: 400,

    remote:'https://api.ctrlv.in',

    easyXDM: easyXDM.noConflict("ctrlv"),

    _open: function() {
        return window.open(this.remote + '/uploader/blank', 'ctrlvuploader', "width=" + this.width + ", height=" + this.height);
    },

    upload: function(callback) {

        this.callback = callback;

        this.popup = this._open();

        var self = this;

        this.proxy = new this.easyXDM.Rpc({

            swf: this.remote + '/assets/easyxdm/easyxdm.swf',

            remote: this.remote + '/uploader/xdframe',

        }, {
            remote: {
                open: {},
                postMessage: {}
            },
            local: {
                postMessage: function(data){
                    try {
                        self.callback(data.image);
                    } catch (e) {
                        console.log(e);
                    }
                }
            }
        });

        // lets tell the proxy to open up the window as soon as possible
        this.proxy.open("ctrlvuploader");

    }

};
