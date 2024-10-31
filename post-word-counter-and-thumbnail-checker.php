<?php
/*
Plugin Name: Post Word Counter and Thumbnail Checker
Description: Simple Post Word Counter and Check which post has thumbnail or not.
Version: 1.0
Author: ratulkhan
Author URI: https://github.com/ratulkhan44
License: GPLv2 or later
Text Domain: word-counter
Domain Path: /languages/
*/
if(!function_exists('pwct_bootstrapping')) {
	function pwct_bootstrapping() {
		load_plugin_textdomain( "word-counter", false, dirname( __FILE__ ) . "/languages" );
	}
}

add_action( 'plugins_loaded', 'pwct_bootstrapping' );

if(!function_exists('pwct_set_word_count')){
	function pwct_set_word_count() {
		$pwct_posts = get_posts( array(
			'posts_per_page' => - 1,
			'post_type'      => 'post',
			'post_status'    => 'any'
		) );

		foreach ( $pwct_posts as $pwct_single_post ) {
			$pwct_content = $pwct_single_post->post_content;
			$pwct_wordn   = str_word_count( strip_tags( $pwct_content ) );
			update_post_meta( $pwct_single_post->ID, 'wordn', $pwct_wordn );
		}
	}
}

add_action( 'init', 'pwct_set_word_count' );

if(!function_exists('pwct_sort_column_data')){
	function pwct_sort_column_data( $pwct_wpquery ) {
		if ( ! is_admin() ) {
			return;
		}

		$pwct_orderby = $pwct_wpquery->get( 'orderby' );
		if ( 'wordn' == $pwct_orderby ) {
			$pwct_wpquery->set( 'meta_key', 'wordn' );
			$pwct_wpquery->set( 'orderby', 'meta_value_num' );
		}
	}
}

add_action( 'pre_get_posts', 'pwct_sort_column_data' );

if(!function_exists('pwct_update_wordcount_on_post_save')){
	function pwct_update_wordcount_on_post_save( $pwct_post_id ) {
		$pwct_single_post       = get_post( $pwct_post_id );
		$pwct_content = $pwct_single_post->post_content;
		$pwct_wordn   = str_word_count( strip_tags( $pwct_content ) );
		update_post_meta( $pwct_single_post->ID, 'wordn', $pwct_wordn );
	}
}

add_action( 'save_post', 'pwct_update_wordcount_on_post_save' );

//Create Column
if(!function_exists('pwct_post_columns')){
	function pwct_post_columns( $pwtc_columns ) {
		$pwtc_columns['id']        = esc_html__( 'Post ID', 'word-counter' );
		$pwtc_columns['thumbnail'] = esc_html__( 'Thumbnail', 'word-counter' );
		$pwtc_columns['wordcount'] = esc_html__( 'Word Count', 'word-counter' );

		return $pwtc_columns;
	}
}

add_filter( 'manage_posts_columns', 'pwct_post_columns' );

if(!function_exists('pwct_post_column_data')){
	function pwct_post_column_data( $pwct_column, $pwct_post_id ) {
		if ( 'id' == $pwct_column ) {
			echo esc_html($pwct_post_id);
		} elseif ( 'thumbnail' == $pwct_column ) {
			$pwct_thumbnail = get_the_post_thumbnail( $pwct_post_id, array( 100, 100 ) );
			if (!empty($pwct_thumbnail)){
				printf("<a href='%s' target='_blank'>%s</a>",esc_url(get_the_permalink($pwct_post_id)),$pwct_thumbnail);
			}else{
				esc_html_e( 'No thumbnail', 'word-counter' );
			}
		} elseif ( 'wordcount' == $pwct_column ) {
			$pwct_wordn = get_post_meta( $pwct_post_id, 'wordn', true );
			echo esc_html($pwct_wordn);
		}
	}
}
add_action( 'manage_posts_custom_column', 'pwct_post_column_data', 10, 2 );

if(!function_exists('pwct_sortable_column')){
	function pwct_sortable_column( $pwtc_columns ) {
		$pwtc_columns['wordcount'] = 'wordn';
		return $pwtc_columns;
	}
}

add_filter( 'manage_edit-post_sortable_columns', 'pwct_sortable_column' );

if(!function_exists('pwtc_wc_filter')){
	function pwtc_wc_filter() {
		if ( sanitize_text_field(isset( $_GET['post_type'] )) && sanitize_text_field($_GET['post_type'] != 'post' )) {
			return;
		}
		$pwtc_filter_value = sanitize_text_field(isset( $_GET['WCFILTER'] )) ? sanitize_text_field($_GET['WCFILTER']) : '';
		$values       = array(
			'0' => esc_html__( 'Select Type', 'word-counter' ),
			'1' => esc_html__( 'Above 400', 'word-counter' ),
			'2' => esc_html__( '200 to 400', 'word-counter' ),
			'3' => esc_html__( 'Below 200', 'word-counter' ),
		);
		?>
        <select name="WCFILTER">
			<?php
			$pwtc_selected='';
			foreach ( $values as $key => $value ) {
				$pwtc_selected=($key == $pwtc_filter_value) ? "selected" : '';
				printf( "<option value='%s' %s>%s</option>", $key,$pwtc_selected,$value);
			}
			?>
        </select>
		<?php
	}
}

add_action( 'restrict_manage_posts', 'pwtc_wc_filter' );

if(!function_exists('word_counter_wc_filter_data')){
	function word_counter_wc_filter_data( $pwtc_wpquery ) {
		if ( ! is_admin() ) {
			return;
		}
		$pwtc_filter_value = sanitize_text_field(isset( $_GET['WCFILTER'] )) ? sanitize_text_field($_GET['WCFILTER']) : '';

		if ( '1' == $pwtc_filter_value ) {
			$pwtc_wpquery->set( 'meta_query', array(
				array(
					'key'     => 'wordn',
					'value'   => 400,
					'compare' => '>=',
					'type'    => 'NUMERIC'
				)
			) );
		} else if ( '2' == $pwtc_filter_value ) {
			$pwtc_wpquery->set( 'meta_query', array(
				array(
					'key'     => 'wordn',
					'value'   => array(200,400),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC'
				)
			) );
		} else if ( '3' == $pwtc_filter_value ) {
			$pwtc_wpquery->set( 'meta_query', array(
				array(
					'key'     => 'wordn',
					'value'   => 200,
					'compare' => '<=',
					'type'    => 'NUMERIC'
				)
			) );
		}


	}
}

add_action( 'pre_get_posts', 'word_counter_wc_filter_data' );

if(!function_exists('pwtc_thumbnail_filter')){
	function pwtc_thumbnail_filter() {
		if ( sanitize_text_field(isset( $_GET['post_type']) ) && sanitize_text_field($_GET['post_type'] != 'post' )) {
			return;
		}
		$pwtc_filter_value = sanitize_text_field(isset( $_GET['THFILTER'] )) ? sanitize_text_field($_GET['THFILTER']) : '';
		$values       = array(
			'0' => esc_html__( 'Select Thumbnail Status', 'word-counter' ),
			'1' => esc_html__( 'Has Thumbnail', 'word-counter' ),
			'2' => esc_html__( 'No Thumbnail', 'word-counter' ),
		);
		?>
        <select name="THFILTER">
			<?php
			foreach ( $values as $key => $value ) {
				printf( "<option value='%s' %s>%s</option>", $key,
					$key == $pwtc_filter_value ? "selected = 'selected'" : '',
					$value
				);
			}
			?>
        </select>
		<?php
	}
}

add_action( 'restrict_manage_posts', 'pwtc_thumbnail_filter' );

if(!function_exists('pwtc_thumbnail_filter_data')){
	function pwtc_thumbnail_filter_data( $pwtc_wpquery ) {
		if ( ! is_admin() ) {
			return;
		}

		$pwtc_filter_value = sanitize_text_field(isset( $_GET['THFILTER'] )) ? sanitize_text_field($_GET['THFILTER']) : '';
		if ( '1' == $pwtc_filter_value ) {
			$pwtc_wpquery->set( 'meta_query', array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS'
				)
			) );
		} else if ( '2' == $pwtc_filter_value ) {
			$pwtc_wpquery->set( 'meta_query', array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'NOT EXISTS'
				)
			) );
		}


	}
}
add_action( 'pre_get_posts', 'pwtc_thumbnail_filter_data' );





