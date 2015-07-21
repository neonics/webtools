module.exports = function(grunt) {

	require('load-grunt-tasks')(grunt);

	// Project configuration & task configuration
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		dirs: {
			css: 'css',
		},

		//The uglify task and its configurations
//		uglify: {
//				 options: {
//							banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
//				 },
//				 build: {
//					 files: [{
//							expand: true,     // Enable dynamic expansion.
//							src: ['resources/**/*.js', '!resources/**/*.min.js'], // Actual pattern(s) to match.
//							ext: '.min.js',   // Dest filepaths will have this extension.
//					 }]
//				 }
//		},
//
		//The jshint task and its configurations
		jshint: {
			all: [ 'assets/**/*.js', '!assets/**/*.min.js' ]
		},

		// Compile all .less files.
		less: {
			compile: {
				options: {
					// These paths are searched for @imports
					paths: ['<%= dirs.css %>/'],
					modifyVars: { 'fa-font-path': '"/fonts/3p"' }
				},
				files: [
					{
						expand: true,
						cwd: '<%= dirs.css %>/',
						src: [
							'**/*.less',
							'!mixins.less',
							'!font-awesome-override.less',
						],
						dest: '<%= dirs.css %>/',
						ext: '.css'
					},

					{
						expand: false,
						src: 'node_modules/font-awesome/less/font-awesome.less',
						dest: '<%= dirs.css %>/3p/font-awesome.css',
						ext: '.css',
					},

/*
					{
						expand: false,
						src: 'css/font-awesome-override.less',
						dest: '<%= dirs.css %>/3p/font-awesome.css',
						ext: '.css',
					},
*/


				]
			}
		},


		// Minify all 3p .css files.
		cssmin: {
			minify: {
				expand: true,
				cwd: 'css/3p/',
				src: ['*.css', '!*.min.css'],
				dest: 'css/3p/',
				ext: '.min.css'
			}
		},



		copy: {
		  main: {
			files: [
			  // includes files within path
			  {expand: true, cwd: 'node_modules/font-awesome/fonts/',	src:['**'], dest: 'fonts/3p/', filter: 'isFile'},
			 // {expand: true, cwd: 'node_modules/font-awesome/css/',		src:['*.min.css'], dest: 'css/3p/', filter: 'isFile'},

			  {expand: true, cwd: 'node_modules/bootstrap/dist/fonts/',	src:['**'], dest: 'fonts/3p/', filter: 'isFile'},
			  {expand: true, cwd: 'node_modules/bootstrap/dist/css/',	src:['bootstrap.min.css'], dest: 'css/3p/', filter: 'isFile'},
			  {expand: true, cwd: 'node_modules/bootstrap/dist/js/',	src:['bootstrap.min.js'], dest: 'js/3p/', filter: 'isFile'},

			  {expand: true, cwd: 'node_modules/jquery/dist/',	src:['**.js'], dest: 'js/3p/', filter: 'isFile'},
			  // includes files within path and its sub-directories
			  //{expand: true, src: ['path/**'], dest: 'dest/'},

			  // makes all src relative to cwd
			  //{expand: true, cwd: 'path/', src: ['**'], dest: 'dest/'},

			  // flattens results to a single level
			  //{expand: true, flatten: true, src: ['path/**'], dest: 'dest/', filter: 'isFile'}
			]
		  }
		},

	});


	grunt.loadNpmTasks('grunt-contrib-copy');

	// Default task(s), executed when you run 'grunt'
	grunt.registerTask('default', [ 'copy', 'less', 'cssmin' ] );

};
