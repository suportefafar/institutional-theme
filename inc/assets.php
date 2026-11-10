<?php
/** Assets locais e dependências das páginas legadas. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action( 'wp_enqueue_scripts', function () {
	$uri = get_stylesheet_directory_uri();
	$dir = get_stylesheet_directory();
	$deps = array( 'farmacia-ds' );
	// Componentes dinâmicos usam o design system. Bootstrap atende apenas conteúdo legado.
	$post = get_queried_object();
	$legacy = false;
	if ( ! is_page( array_keys( fafar_dynamic_routes() ) ) && $post instanceof WP_Post && preg_match( '/(?:class=[\'\"][^\'\"]*\b(?:row|col(?:-\w+)?|btn|table|container|list-group|d-flex)\b|data-bs-)/', $post->post_content ) ) { $legacy = true; }
	if ( ! is_page( array_keys( fafar_dynamic_routes() ) ) && $post instanceof WP_Post && ( has_shortcode( $post->post_content, 'contact-form-7' ) || has_shortcode( $post->post_content, 'institutional_fafar_ebook_show' ) ) ) { $legacy = true; }
	if ( $legacy ) {
		wp_enqueue_style( 'fafar-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css', array(), '5.3.2' );
		wp_enqueue_style( 'fafar-bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css', array(), '1.11.3' );
		wp_enqueue_script( 'fafar-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array(), '5.3.2', true );
		$deps[] = 'fafar-bootstrap';
	}
	wp_enqueue_style( 'farmacia-ds', $uri . '/assets/css/farmacia-ds.css', array(), filemtime( $dir . '/assets/css/farmacia-ds.css' ) );
	wp_enqueue_style( 'fafar-theme', $uri . '/style.css', $deps, filemtime( $dir . '/style.css' ) );
	wp_enqueue_script( 'fafar-navigation', $uri . '/assets/js/navigation.js', array(), filemtime( $dir . '/assets/js/navigation.js' ), true );
	if ( is_page( 'matricula-disciplina-isolada-ppgmaf-personalizada' ) ) {
		wp_enqueue_style( 'fafar-ppgmaf-registration', $uri . '/assets/css/ppgmaf-registration.css', array( 'farmacia-ds' ), filemtime( $dir . '/assets/css/ppgmaf-registration.css' ) );
		wp_enqueue_script( 'fafar-ppgmaf-registration', $uri . '/assets/js/ppgmaf-registration.js', array(), filemtime( $dir . '/assets/js/ppgmaf-registration.js' ), true );
	}
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) { wp_enqueue_script( 'comment-reply' ); }
} );
add_action( 'wp_head', function () { echo '<meta name="theme-color" content="#f2b600">'; } );
