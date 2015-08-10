module.exports = function(grunt) {

    function generateBuildNumber(){
        var d = new Date();
        return d.getUTCFullYear() + ('0' + (d.getUTCMonth()+1)).slice(-2) + ('0' + d.getUTCDate()).slice(-2) + ('0' + d.getUTCHours()).slice(-2) + ('0' + d.getUTCMinutes()).slice(-2);
    }
    var timestamp = generateBuildNumber();

    grunt.initConfig({

        less: {
            'build-upload-less': {
                options: {
                    compress: true,
                    yuicompress: true,
                    sourceMap: true,
                    outputSourceFiles: true,
                    sourceMapURL: 'upload.min.css.map',
                    sourceMapFilename: 'public/assets/build/' + timestamp + '/css/upload.min.css.map',
                    sourceMapBasepath: 'resources/assets/less',

                },
                src: [
                    'resources/assets/less/upload.less'
                ],
                dest: 'public/assets/build/' + timestamp + '/css/upload.min.css'
            }
        },

        uglify: {
            options: {
                mangle: false,
                sourceMap: true,
                sourceMapIncludeSources: true
            },
            'build-upload-js': {
                src: [
                    'resources/assets/js/ImagePaster.js',
                    'resources/assets/js/upload.js'
                ],
                dest: 'public/assets/build/' + timestamp + '/js/upload.min.js'
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
            'build': {
                files: [
                    'resources/assets/less/**/*.*',
                    'resources/assets/js/**/*.*'
                ],
                tasks: ['build']
            }
        },

    });

    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerTask('build', function() {

        // clean build folder
        grunt.task.run(['clean:pre-build']);

        // less -> minified css
        //grunt.task.run(['less:build-less']);
        grunt.task.run(['less:build-upload-less']);

        // minify js
        grunt.task.run(['uglify:build-upload-js']);

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


};


