module.exports = function(grunt) {

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    composer: grunt.file.readJSON('composer.json'),
    clean: {
      main: ['assets'],
      develop: {
        options: {force: true},
        src: ['../../../../../../../public/foolfuuka/foolz/foolfuuka-theme-foolfuuka/']
      }
    } ,
    less: {
      production: {
        files: {
          'assets/css/style.css': 'assets-src/less/style.less'
        }
      }
    },
    copy: {
      main: {
        files: [
          {expand: true, cwd: 'assets-src/font-awesome/fonts/', src: ['**'], dest: 'assets/fonts/'},
          {expand: true, cwd: 'assets-src/images/', src: ['**'], dest: 'assets/images/'},
          {expand: true, cwd: 'assets-src/js/', src: ['**'], dest: 'assets/js/'}
        ]
      },
      develop: {
        files: [
          {
            expand: true,
            cwd: 'assets/',
            src: ['**'],
            dest: '../../../../../../../public/foolfuuka/foolz/foolfuuka-theme-foolfuuka/assets-<%= composer.version %>/'
          }
        ]
      }
    },
    watch: {
      scripts: {
        files: ['assets-src/less/**'],
        tasks: ['clean', 'less', 'copy'],
        options: {
          spawn: false
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-less');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-watch');

  grunt.registerTask('default', ['clean:main', 'less', 'copy:main']);
  grunt.registerTask('dev', ['clean', 'less', 'copy']);

};
