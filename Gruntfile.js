module.exports = function(grunt) {

    function generateBuildNumber(){
        var d = new Date();
        return d.getUTCFullYear() + ('0' + (d.getUTCMonth()+1)).slice(-2) + ('0' + d.getUTCDate()).slice(-2) + ('0' + d.getUTCHours()).slice(-2) + ('0' + d.getUTCMinutes()).slice(-2);
    }
    var timestamp = generateBuildNumber();

    grunt.initConfig({

        less: {
            'build-uploader-less': {
                options: {
                    compress: true,
                    yuicompress: true,
                    sourceMap: true,
                    outputSourceFiles: true,
                    sourceMapURL: 'uploader.min.css.map',
                    sourceMapFilename: 'public/assets/build/' + timestamp + '/css/upload.min.css.map',
                    sourceMapBasepath: 'resources/assets/less/uploader',

                },
                src: [
                    'resources/assets/uploader/less/uploader.less'
                ],
                dest: 'public/assets/build/' + timestamp + '/css/uploader.min.css'
            }
        },

        uglify: {
            options: {
                mangle: false,
                sourceMap: true,
                sourceMapIncludeSources: true
            },

            // Scripts used in the uploader popup
            'build-uploader-js': {
                src: [
                    'resources/assets/js/src/ImagePaster.js',
                    'resources/assets/uploader/js/uploader.js'
                ],
                dest: 'public/assets/build/' + timestamp + '/js/uploader.min.js'
            },

            // Scripts used on third party sites to launch the uploader
            'build-sdk-js': {
                src: [
                    'public/easyxdm/easyXDM.min.js',
                    'resources/assets/sdk/js/upload.js'
                ],
                dest: 'public/upload.js'
            }
        },

        clean: {
            /**
             * Remove existing build files
             */
            'pre-build': {
                src: ['public/assets/build/']
            },
            'post-build': {
                src: []
            }
        },

        watch: {
            'uploader': {
                files: [
                    'resources/assets/uploader/**/*.*',
                ],
                tasks: ['build-uploader']
            }
        },

    });

    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerTask('build-uploader', function() {

        // clean build folder
        grunt.task.run(['clean:pre-build']);

        // less -> minified css
        //grunt.task.run(['less:build-less']);
        grunt.task.run(['less:build-uploader-less']);

        // minify js
        grunt.task.run(['uglify:build-uploader-js']);

        // Save version number to be used in PHP
        grunt.task.run(['write-version']);

    });

    grunt.registerTask('write-version', function() {
        var versionFileContents = "<?php return array('version' => " + timestamp  + ");";
        var versionFilePath = 'config/assets.php';
        if (grunt.file.write(versionFilePath, versionFileContents)) {
            grunt.log.writeln('Wrote ' + versionFilePath);
        }
    });

    /**
     * Build the JS SDK to be used by third parties
     */
    grunt.registerTask('build-sdk', function() {
        grunt.task.run(['uglify:build-sdk-js']);
    });

};


