var gulp = require('gulp');
var sass = require('gulp-sass');
var browserSync = require('browser-sync').create();
var rename = require('gulp-rename');
var cached = require('gulp-cached');

var scssFiles = [
  'docroot/**/humsci/**/scss/**/[a-z]*.scss',
  '!**/node_modules/**/*.scss',
  '!deploy/**/*.scss'
];

var includePaths = [
  "node_modules/bourbon/core",
  "node_modules/bourbon-neat/app/assets/stylesheets",
  "node_modules/neat-omega/core",
  "node_modules/decanter/scss",
  "node_modules/decanter/scss/decanter-no-markup",
  "node_modules"
];

gulp.task('sass', function () {
  return gulp.src(scssFiles)
    .pipe(cached('sassfiles'))
    .pipe(sass({
      outputStyle: 'compressed',
      includePaths: includePaths
    }))
    .pipe(rename(function (path) {
      path.dirname = path.dirname.replace('scss', 'css');
    }))
    .pipe(gulp.dest('docroot'))
    .pipe(browserSync.reload({
      stream: true
    }))
});

gulp.task('browserSync', function () {
  browserSync.init({
    server: {
      baseDir: 'docroot'
    },
  })
});

gulp.task('watch', ['sass'], function () {
  gulp.watch(scssFiles, ['sass']);
  gulp.watch('**/humsci/**/*.js', browserSync.reload);
});