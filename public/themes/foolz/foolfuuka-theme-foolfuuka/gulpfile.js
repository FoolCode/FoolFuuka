var es = require('event-stream'),
    gulp = require('gulp'),
    rimraf = require('gulp-rimraf'),
    less = require('gulp-less'),
    browserify = require('gulp-browserify'),
    runSequence = require('run-sequence'),
    composer = require('./composer.json');

gulp.task('clean', function() {
  return gulp.src('assets', {read: false}).pipe(rimraf({force: true}));
});

gulp.task('clean:dev', function() {
  return gulp.src('../../../../../../../public/foolfuuka/foolz/foolfuuka-theme-foolfuuka/', {read: false})
    .pipe(rimraf({force: true}));
});

gulp.task('less', function() {
  return gulp.src('assets-src/less/style.less').pipe(less()).pipe(gulp.dest('assets/css'));
});

gulp.task('copy', function() {
  return es.merge(
    gulp.src('assets-src/font-awesome/fonts/**').pipe(gulp.dest('assets/fonts/')),
    gulp.src('assets-src/images/**').pipe(gulp.dest('assets/images/'))
  );
});

gulp.task('browserify', function() {
  return gulp.src('assets-src/js/app.js')
    .pipe(browserify())
    .pipe(gulp.dest('assets/js/'))
});

gulp.task('copy:dev', function() {
  return gulp.src('assets/**')
    .pipe(gulp.dest('../../../../../../../public/foolfuuka/foolz/foolfuuka-theme-foolfuuka/assets-' + composer.version + '/'));
});

gulp.task('default', function(cb) {
  runSequence('clean', ['less', 'copy'], cb);
});

gulp.task('dev', function(cb) {
  runSequence(['clean', 'clean:dev'], ['less', 'copy', 'browserify'], 'copy:dev', cb);
});

gulp.task('watch', function() {
  gulp.watch('assets-src/less/**', ['dev']);
  gulp.watch('assets-src/js/**', ['dev']);
});
