var path = require('path');
const replace = require('gulp-replace')
var fs = require('fs');
var pkg = JSON.parse(fs.readFileSync('./package.json'));
// var assetsPath = path.resolve(pkg.path.assetsDir);
// var cleanCss = require('gulp-clean-css');

var gulp = require('gulp');

// var cmq = require('gulp-merge-media-queries');

// error handling
// var plumber = require('gulp-plumber');
// gulp.task('sass', function(done) {
//     gulp.src('./assets/_scss/*.scss')
//         .pipe(sass())
// 		.pipe(cmq({log:true}))
//         // .pipe(autoprefixer())
// 		.pipe(cleanCss())
// 		.pipe(gulp.dest('./assets/css/'));
// 		done();
// });

gulp.task('replace_text_domain', function (done) {
	// font-awesome.
	gulp.src(["./inc/font-awesome/package/*.php"])
		.pipe(replace("vk_font_awesome_version_textdomain","vk-post-author-display"))
		.pipe(gulp.dest("./inc/font-awesome/package/"));
	// template-tags.
	gulp.src(["./inc/template-tags/package/*.php"])
		.pipe(replace("template_tags_textdomain","vk-post-author-display"))
		.pipe(gulp.dest("./inc/template-tags/package/"));
	// term-color.
	gulp.src(["./inc/term-color/package/*.php"])
		.pipe(replace("vk_term_color_textdomain","vk-post-author-display"))
		.pipe(gulp.dest("./inc/term-color/package/"));
	done();
  });

// gulp.task('default', function() {
//     gulp.watch('./assets/_scss/**.scss',gulp.task('watch'));
// });

// gulp.task('watch', function() {
// 	gulp.watch('./assets/_scss/**/*.scss',gulp.parallel('sass'));
// });

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
// 				'./assets/**',
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
