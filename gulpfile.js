var path = require('path');
var fs = require('fs');
var pkg = JSON.parse(fs.readFileSync('./package.json'));
// var assetsPath = path.resolve(pkg.path.assetsDir);
var cleanCss = require('gulp-clean-css');

var gulp = require('gulp');

// sass compiler
var sass = require('gulp-sass');

var cmq = require('gulp-merge-media-queries');
//
// // add vender prifix
// var autoprefixer = require('gulp-autoprefixer');

// error handling
// var plumber = require('gulp-plumber');
gulp.task('sass', function(done) {
    gulp.src('./_scss/*.scss')
        .pipe(sass())
		.pipe(cmq({log:true}))
        // .pipe(autoprefixer())
		.pipe(cleanCss())
		.pipe(gulp.dest('./css/'));
		done();
});

gulp.task('default', function() {
    gulp.watch('_scss/**.scss',gulp.task('watch'));
});

gulp.task('watch', function() {
	gulp.watch('_scss/**/*.scss',gulp.parallel('sass'));
});

// copy dist ////////////////////////////////////////////////

// gulp.task('dist', function() {
// 	return gulp.src(
// 			[
// 				'./**/*.php',
// 				'./**/*.txt',
// 				'./**/*.css',
// 				'./**/*.scss',
// 				'./**/*.bat',
// 				'./**/*.rb',
// 				'./**/*.eot',
// 				'./**/*.svg',
// 				'./**/*.ttf',
// 				'./**/*.woff',
// 				'./images/**',
// 				'./inc/**',
// 				'./js/**',
// 				'./languages/**',
// 				"!./tests/**",
// 				"!./dist/**",
//         "!./**/compile.bat",
// 				"!./node_modules/**/*.*"
// 			], {
// 				base: './'
// 			}
// 		)
// 		.pipe(gulp.dest('dist/lightning-bbpress-extension')); // distディレクトリに出力
// });
