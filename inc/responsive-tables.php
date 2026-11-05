<?php
/** Responsive table markup shared by editorial and generated content. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Add a class to an opening HTML tag while preserving its other attributes. */
function fafar_table_add_class( $tag, $class ) {
	if ( preg_match( '/\bclass\s*=\s*(["\'])(.*?)\1/isu', $tag, $match ) ) {
		$classes = preg_split( '/\s+/u', trim( $match[2] ) );
		if ( in_array( $class, $classes, true ) ) { return $tag; }
		return preg_replace_callback(
			'/\bclass\s*=\s*(["\'])(.*?)\1/isu',
			function ( $class_match ) use ( $class ) {
				return 'class=' . $class_match[1] . trim( $class_match[2] . ' ' . $class ) . $class_match[1];
			},
			$tag,
			1
		);
	}
	return preg_replace( '/\s*\/?>$/u', ' class="' . $class . '"$0', $tag, 1 );
}

/**
 * Follow Bootstrap's responsive table pattern without rewriting stored content.
 * Existing wrappers are reused and nested tables share their nearest wrapper.
 */
function fafar_responsive_tables( $html ) {
	if ( false === stripos( $html, '<table' ) ) { return $html; }
	$tokens = preg_split( '/(<[^>]+>)/su', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	$div_stack = array();
	$table_stack = array();
	$responsive_depth = 0;
	$output = '';
	foreach ( $tokens as $token ) {
		if ( preg_match( '/^<div\b/i', $token ) ) {
			$is_responsive = (bool) preg_match( '/\bclass\s*=\s*(["\'])[^"\']*\btable-responsive\b[^"\']*\1/isu', $token );
			$div_stack[] = $is_responsive;
			if ( $is_responsive ) { $responsive_depth++; }
			$output .= $token;
			continue;
		}
		if ( preg_match( '/^<\/div\s*>/i', $token ) ) {
			$is_responsive = array_pop( $div_stack );
			if ( $is_responsive ) { $responsive_depth--; }
			$output .= $token;
			continue;
		}
		if ( preg_match( '/^<table\b/i', $token ) ) {
			$wrapped = 0 === $responsive_depth;
			if ( $wrapped ) { $output .= '<div class="table-responsive">'; $responsive_depth++; }
			$output .= fafar_table_add_class( $token, 'table' );
			$table_stack[] = $wrapped;
			continue;
		}
		if ( preg_match( '/^<\/table\s*>/i', $token ) ) {
			$output .= $token;
			$wrapped = array_pop( $table_stack );
			if ( $wrapped ) { $output .= '</div>'; $responsive_depth--; }
			continue;
		}
		$output .= $token;
	}
	return $output;
}

add_filter( 'the_content', 'fafar_responsive_tables', 25 );
