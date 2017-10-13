module.exports = function (grunt) {
    var min = {},
        path = require('path'),
        srcpath = 'Resources/public/js',
        destpath = 'Resources/public/dist';

    // Build config "min" object dynamically.
    grunt.file.expand({cwd: srcpath}, '**/*.js').forEach(function(relpath) {
        // Create a target Using the verbose "target: {src: src, dest: dest}" format.
        min[relpath] = {
            src: path.join(srcpath, relpath),
            dest: path.join(destpath, relpath)
        };
        // The more compact "dest: src" format would work as well.
        // min[path.join(destpath, relpath)] = path.join(srcpath, relpath);
    });

    // load all grunt tasks
    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        copy: {
        },
        clean: {
            options: { force: true }
        },
        watch: {
            options: {
                nospawn: true
            },
            compass: {
                files: ['Resources/public/scss/{,*/}*.{scss,sass}'],
                tasks: ['compass:dev']
            },
            scripts: {
                files: ['Resources/public/**'],
                tasks: ['build']
            }
        },
        jshint: {
            options: {
                jshintrc: '.jshintrc'
            }
        },
        cssmin: {
            // TODO: options: { banner: '<%= meta.banner %>' },
            compress: {
                files: {
                    'dist/main.min.css': ['Resources/public/css/']
                }
            }
        },
        compass: {
            dev: {
                options: {
                    sassDir: 'Resources/public/scss/',
                    specify: ['Resources/public/scss/main.scss'],
                    cssDir: 'Resources/public/css/',
                    relativeAssets: false
                }
            }
        },
        uglify: min,
        replace: {
            build: {
                options: {
                    variables: {
                        'sulucontent/js': 'sulucontent/dist'
                    },
                    prefix: ''
                },
                files: [
                    {src: ['Resources/public/dist/main.js'], dest: 'Resources/public/dist/main.js'}
                ]
            }
        }
    });

    grunt.registerTask('build', [
        'uglify',
        'replace:build'
    ]);

    grunt.registerTask('default', [
        'watch'
    ]);
};
