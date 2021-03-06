<?php
/**
 * Loads all assets on the front-end.
 *
 * @package   rating-report
 * @copyright Copyright (c) 2016, Nose Graze Ltd.
 * @license   GPL2+
 */

/**
 * Register front-end stylesheet.
 *
 * @since 2.0
 * @return void
 */
function rating_report_register_styles() {
	if ( rating_report_get_option( 'disable_styles', false ) ) {
		return;
	}

	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	/*
	 * Load Font Awesome if we need to.
	 */

	if ( rating_report_get_option( 'include_font_awesome', false ) ) {
		wp_enqueue_style( 'font-awesome', RATING_REPORT_PLUGIN_URL . '/assets/css/font-awesome' . $suffix . '.css', array(), '4.6.1' );
	}

	/*
	 * Main Rating Report stylesheet.
	 */

	$filename = 'rating-report-front-end' . $suffix . '.css';
	$url      = RATING_REPORT_PLUGIN_URL . '/assets/css/' . $filename;

	wp_register_style( 'rating-report', apply_filters( 'rating-report/css-file', $url ), array(), RATING_REPORT_VERSION, 'all' );
	wp_enqueue_style( 'rating-report' );

	// Add inline CSS
	wp_add_inline_style( 'rating-report', rating_report_generate_css() );
}

add_action( 'wp_enqueue_scripts', 'rating_report_register_styles' );

/**
 * Generate CSS based on style settings.
 *
 * @since 2.0
 * @return string
 */
function rating_report_generate_css() {
	$css = '';

	$bar_color = rating_report_get_option( 'bar_color', '#3CB2D2' );
	if ( $bar_color ) {
		$css .= '.rating-report-bar { background: ' . esc_attr( $bar_color ) . ' }';
	}

	$bar_bg = rating_report_get_option( 'bar_bg', '#eeeeee' );
	if ( $bar_bg ) {
		$css .= '.rating-report-graph-values .rating-report-category, .rating-report-bar-wrap { background: ' . esc_attr( $bar_bg ) . ' }';
	}

	return apply_filters( 'rating-report/generated-css', $css );
}