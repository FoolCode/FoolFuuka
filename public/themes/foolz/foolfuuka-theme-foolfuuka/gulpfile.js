var gulp = require('gulp');
var rimraf = require('gulp-rimraf');
var less = require('gulp-less');
var composer = require('./composer.json');

gulp.task('clean', function() {
  gulp.src('assets', {read: false}).pipe(rimraf({force: true}));
});

gulp.task('clean:dev', function() {
  gulp.src('../../../../../../../public/foolfuuka/foolz/foolfuuka-theme-foolfuuka/', {read: false})
    .pipe(rimraf({force: true}));
});

gulp.task('less', function() {
  gulp.src('assets-src/less/style.less').pipe(less()).pipe(gulp.dest('assets/css'));
});

gulp.task('copy', function() {
  gulp.src('assets-src/font-awesome/fonts/**').pipe(gulp.dest('assets/fonts/'));
  gulp.src('assets-src/images/**').pipe(gulp.dest('assets/images/'));
  gulp.src('assets-src/js/**').pipe(gulp.dest('assets/js/'));
});

gulp.task('copy:dev', function() {
  gulp.src('assets/**')
    .pipe(gulp.dest('../../../../../../../public/foolfuuka/foolz/foolfuuka-theme-foolfuuka/assets-' + composer.version + '/'));
});

gulp.task('default', ['clean', 'less', 'copy'], function() {

});
