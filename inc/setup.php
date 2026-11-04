<?php
/** Estrutura fixa do tema institucional. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'custom-logo', array( 'height' => 96, 'width' => 280, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus( array( 'primary' => 'Navegação principal', 'secondary_menu' => 'Menu lateral da página inicial', 'footer_menu' => 'Links do rodapé' ) );
	$GLOBALS['content_width'] = 880;
} );

add_action( 'widgets_init', function () {
	$areas = array( 'sidebar-1' => 'Barra lateral', 'header-widget' => 'Idiomas no cabeçalho' );
	// Mantém os IDs das áreas existentes para aproveitar os widgets já cadastrados.
	for ( $i = 1; $i <= 6; $i++ ) { $areas[ 'footer-widget-' . $i ] = 'Rodapé ' . $i; }
	for ( $i = 1; $i <= 4; $i++ ) { $areas[ 'advanced-footer-widget-' . $i ] = 'Rodapé adicional ' . $i; }
	foreach ( $areas as $id => $name ) {
		register_sidebar( array( 'id' => $id, 'name' => $name, 'before_widget' => '<section id="%1$s" class="widget %2$s">', 'after_widget' => '</section>', 'before_title' => '<h2 class="widget-title">', 'after_title' => '</h2>' ) );
	}
} );
