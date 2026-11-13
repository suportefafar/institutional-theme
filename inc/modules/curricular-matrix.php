<?php
/** Consulta das matrizes publicadas pelos colegiados. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function fafar_is_curricular_matrix() {
	return is_page( array( 'da-matriz-curricular-far', 'da-matriz-curricular-bio' ) ) && isset( $_GET['matriz'] ) && '1' === $_GET['matriz'];
}
add_filter( 'the_content', function ( $content ) {
	if ( is_main_query() && in_the_loop() && is_page( array( 'da-matriz-curricular-far', 'da-matriz-curricular-bio' ) ) ) {
		$content = '<p><a href="' . esc_url( add_query_arg( 'matriz', '1', get_permalink() ) ) . '">Consultar a matriz curricular por período →</a></p>' . $content;
	}
	return $content;
} );
add_action( 'wp_enqueue_scripts', function () {
	if ( ! fafar_is_curricular_matrix() ) { return; }
	$dir = get_stylesheet_directory(); $uri = get_stylesheet_directory_uri();
	wp_enqueue_style( 'fafar-curricular-matrix', $uri . '/assets/css/curricular-matrix.css', array( 'fafar-theme' ), filemtime( $dir . '/assets/css/curricular-matrix.css' ) );
	wp_enqueue_script( 'fafar-curricular-matrix', $uri . '/assets/js/curricular-matrix.js', array(), filemtime( $dir . '/assets/js/curricular-matrix.js' ), true );
} );
add_filter( 'template_include', function ( $template ) {
	return fafar_is_curricular_matrix() ? get_stylesheet_directory() . '/template-matriz-curricular.php' : $template;
} );

/** Rota de entrada compartilhada, sem exigir uma página cadastrada no banco. */
add_filter( 'request', function ( $vars ) {
	if ( isset( $vars['pagename'] ) && 'matriz-curricular' === $vars['pagename'] && ! get_page_by_path( 'matriz-curricular' ) ) {
		$vars['pagename'] = 'da-matriz-curricular-bio';
		$GLOBALS['fafar_curricular_index'] = true;
	}
	return $vars;
} );
add_filter( 'redirect_canonical', function ( $redirect ) {
	return ! empty( $GLOBALS['fafar_curricular_index'] ) ? false : $redirect;
} );
add_filter( 'template_include', function ( $template ) {
	return ( ! empty( $GLOBALS['fafar_curricular_index'] ) || is_page( 'matriz-curricular' ) ) ? get_stylesheet_directory() . '/template-matriz-curricular-index.php' : $template;
}, 20 );
add_filter( 'wp_nav_menu_objects', function ( $items, $args ) {
	$menu = wp_get_nav_menu_object( $args->menu );
	if ( 'menu-alunos' !== $args->menu_id && ( ! $menu || 'alunos' !== $menu->slug ) ) { return $items; }
	foreach ( $items as $item ) {
		if ( untrailingslashit( $item->url ) === untrailingslashit( home_url( '/matriz-curricular/' ) ) ) { return $items; }
	}
	if ( ! $items ) { return $items; }
	$entry = clone reset( $items );
	$entry->ID = -6340;
	$entry->db_id = -6340;
	$entry->menu_item_parent = 0;
	$entry->title = 'Matriz curricular';
	$entry->url = home_url( '/matriz-curricular/' );
	$entry->type = 'custom';
	$entry->object = 'custom';
	$entry->object_id = 0;
	$entry->classes = array( 'menu-item', 'menu-item-type-custom', 'menu-item-object-custom' );
	$entry->current = ! empty( $GLOBALS['fafar_curricular_index'] ) || is_page( 'matriz-curricular' );
	$entry->current_item_ancestor = false;
	$entry->current_item_parent = false;
	$items[] = $entry;
	return $items;
}, 10, 2 );
