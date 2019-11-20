<?php
if ( ! class_exists( 'Vk_Post_Author_Box' ) ) {

	class Vk_Post_Author_Box {

		public static function pad_get_author_profile() {
			global $post;
			$user_id   = $post->post_author;
			$user      = get_userdata( $user_id );
			$options   = pad_get_plugin_options();
			$user_name = esc_html( get_the_author_meta( 'display_name' ) );

			// author caption
			if ( get_the_author_meta( 'pad_caption' ) ) {
				$caption = '<span id="pad_caption" class="pad_caption">' . get_the_author_meta( 'pad_caption' ) . '</span>';
			}

			$author_picture_style = ( ! isset( $options['author_picture_style'] ) || ! $options['author_picture_style'] ) ? 'square' : $options['author_picture_style'];

			$profileUnit      = '<div id="avatar" class="avatar ' . esc_attr( $author_picture_style ) . '">';
			$profile_image_id = get_the_author_meta( 'user_profile_image' );
			if ( $profile_image_id ) {
				$profile_image_src = wp_get_attachment_image_src( $profile_image_id, 'thumbnail' );
				$profileUnit      .= '<img src="' . $profile_image_src[0] . '" alt="' . $user_name . '" />';
			} else {
				$profileUnit .= get_avatar( get_the_author_meta( 'email' ), 100 );
			}
			$profileUnit .= '</div><!-- [ /#avatar ] -->';

			$profileUnit .= '<dl id="profileTxtSet" class="profileTxtSet">' . "\n";
			$profileUnit .= '<dt>' . "\n";
			$profileUnit .= '<span id="authorName" class="authorName">' . $user_name . '</span>';

			if ( isset( $caption ) ) :
				$profileUnit .= $caption;
			endif;

			$profileUnit .= '</dt><dd>' . "\n";
			$profileUnit .= wp_kses_post( nl2br( get_the_author_meta( 'description' ) ) ) . "\n";

			$sns_array = pad_sns_array();
			$sns_icons = '';

			// Set Font Awesome Version
			$fa = '5_WebFonts_CSS';

			if ( class_exists( 'Vk_Font_Awesome_Versions' ) ) {
				$fa_versions = new Vk_Font_Awesome_Versions();
				if ( method_exists( $fa_versions, 'get_option_fa' ) ) {
					$fa = $fa_versions::get_option_fa();
				}
			}

			// url
			$url = isset( $user->data->user_url ) ? $user->data->user_url : '';
			if ( $url ) {
				if ( $fa == '4.7' ) {
					$icon = 'fa fa-globe web';
				} else {
					$icon = 'fas fa-globe';
				}
				$sns_icons = '<li class="pad_url"><a href="' . esc_url( $url ) . '" target
				="_blank" class="web"><i class="' . $icon . '" aria-hidden="true"></i></a></li>';
			}

			// SNS Brand Icon
			if ( $fa == '4.7' ) {
				$fa_before = 'fa ';
			} else {
				$fa_before = 'fab ';
			}

			foreach ( $sns_array as $key => $value ) {
				$field   = 'pad_' . $key;
				$sns_url = get_the_author_meta( $field );

				// 旧バージョンの人はアカウントだけで保存されているので、その前のURLを追加
				if ( $key == 'twitter' && $sns_url ) {
					$subject = $sns_url;
					$pattern = '/https:\/\/twitter.com\//';
					preg_match( $pattern, $subject, $matches, PREG_OFFSET_CAPTURE );
					if ( ! $matches ) {
						$sns_url = 'https://twitter.com/' . $sns_url;
					}
				} // if ( $key == 'twitter' ){

				if ( $sns_url ) {
					$sns_icons .= '<li class="pad_' . $key . '"><a href="' . esc_url( $sns_url ) . '" target
					="_blank" class="' . $key . '"><i class="' . $fa_before . $value['icon'] . '" aria-hidden="true"></i></a></li>';
				}
			}

			if ( $sns_icons ) {
				$profileUnit .= '<ul class="sns_icons">';
				$profileUnit .= $sns_icons;
				$profileUnit .= '</ul>';
			}
			$profileUnit .= '</dd></dl>';
			return $profileUnit;
		}

		public static function pad_get_author_entries() {
			$options = pad_get_plugin_options();

			// author entries
			global $post;
			$args       = array(
				'post_type'      => $post->post_type,
				'posts_per_page' => 4,
				'author'         => $post->post_author,
			);
			$args       = apply_filters( 'pad_get_author_entries_args', $args );
			$loop       = new WP_Query( $args );
			$entryUnit  = '<div id="latestEntries">' . "\n";
			$entryUnit .= '<h5>' . wp_kses_post( $options['list_box_title'] ) . '</h5>' . "\n";
			if ( $options['author_archive_link'] == 'display' ) {
					$entryUnit .= '<p class="authorLink"><a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '" rel="author"><i class="fa fa-chevron-circle-right" aria-hidden="true"></i> ' . wp_kses_post( $options['author_archive_link_txt'] ) . '</a></p>' . "\n";
			}
			$entryUnit .= '<ul class="entryList">' . "\n";
			while ( $loop->have_posts() ) :
				$loop->the_post();
				$term = '';

				$taxonomies = get_the_taxonomies();
				if ( $taxonomies ) :
					// get $taxonomy name
					$taxonomy   = key( $taxonomies );
					$terms      = get_the_terms( get_the_ID(), $taxonomy );
					$term_name  = esc_html( $terms[0]->name );
					$term_color = Vk_term_color::get_term_color( $terms[0]->term_id );
					$term_color = ( $term_color ) ? ' style="background-color:' . $term_color . '"' : '';
					$term_link  = esc_url( get_term_link( $terms[0]->term_id, $taxonomy ) );
					$term       = '<a class="padCate"' . $term_color . ' href="' . $term_link . '">' . $term_name . '</a>';
				endif;

				if ( $options['show_thumbnail'] == 'hide' ) {
					/* list only */
					$entryUnit .= '<li class="textList"><span class="padDate">' . get_the_date( 'Y.m.d' ) . '</span>' . $term . '<a href="' . get_permalink( $post->ID ) . '" class="padTitle">' . get_the_title() . '</a></li>' . "\n";
				} else {
					/* Show thumbnail box */
					$entryUnit .= '<li class="thumbnailBox"><span class="postImage"><a href="' . get_permalink() . '">';
					if ( has_post_thumbnail() ) {
						//allows display of pad_thumb only if selected in pad options
						$sizes_available = get_intermediate_image_sizes();

						if ( in_array( 'pad_thumb', $sizes_available ) ) {
							$pad_thumb = get_the_post_thumbnail( get_the_ID(), 'pad_thumb' );
						} elseif ( in_array( 'post-thumbnail', $sizes_available ) ) {
							$pad_thumb = get_the_post_thumbnail( get_the_ID(), 'post-thumbnail' );
						} else {
							$pad_thumb = get_the_post_thumbnail( get_the_ID(), 'thumbnail' );
						}

						$entryUnit .= $pad_thumb;

					} else {
						$entryUnit .= '<img src="' . plugins_url() . '/vk-post-author-display/images/thumbnailDummy.jpg" alt="' . get_the_title() . '" />';
					}
					$entryUnit .= $term . '</a></span><span class="padDate">' . get_the_date( 'Y.m.d' ) . '</span><a href="' . get_permalink( $post->ID ) . '" class="padTitle">' . get_the_title() . '</a></li>' . "\n";
				}
				endwhile;
			$entryUnit .= '</ul>' . "\n";
			$entryUnit .= '</div>' . "\n";
			/* トップページが固定ページで is_page() でも pad を使おうとすると wp_reset_query() があるとthe_post_thumbnailが誤動作する */
			wp_reset_query(); // important!!
			return $entryUnit;
		}

		public static function pad_get_author_box( $layout = 'normal' ) {
			$options     = pad_get_plugin_options();
			$author_unit = '<div class="padSection" id="padSection">';

			if ( $layout != 'author_archive' ) {
				$author_unit .= '<h4>' . esc_html( $options['author_box_title'] ) . '</h4>';
			}

			$author_unit .= Vk_Post_Author_Box::pad_get_author_profile();

			if ( $layout != 'author_archive' ) {
				$author_unit .= Vk_Post_Author_Box::pad_get_author_entries();
			}

			$author_unit .= '</div>';
			return $author_unit;
		}
	}
}
