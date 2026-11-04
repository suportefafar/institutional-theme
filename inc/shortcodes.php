<?php
add_shortcode( 'site_fafar_custom_sidebar_menu', 'site_fafar_custom_sidebar_menu' );
add_shortcode( 'site_fafar_highlight_area', 'site_fafar_highlight_area' );

/** Shortcodes return markup so they also work inside blocks and widgets. */
function site_fafar_custom_sidebar_menu() {
	$menu = get_menu_by_current_content();
	$category = institutional_fafar_get_category();
	if ( $category ) { $menu = $category->slug; }
	if ( is_home() || is_front_page() ) { $menu = get_secondary_menu(); }
	$html = fafar_menu( array( 'menu' => $menu, 'menu_class' => 'section-menu', 'depth' => 0 ) );
	if ( ! $html ) { $html = fafar_menu( array( 'menu' => get_institucional_menu(), 'menu_class' => 'section-menu' ) ); }
	return $html;
}

function get_institucional_menu() { return 4; }
function get_secondary_menu() {
	$locations = get_nav_menu_locations();
	return isset( $locations['secondary_menu'] ) ? $locations['secondary_menu'] : get_institucional_menu();
}
function get_menu_by_current_content() {
	if ( is_singular() ) {
		$categories = get_the_category();
		if ( ! empty( $categories ) ) { return $categories[0]->slug; }
		$author = get_userdata( (int) get_post_field( 'post_author', get_queried_object_id() ) );
		if ( $author ) { return $author->user_nicename; }
	}
	return get_institucional_menu();
}

function site_fafar_highlight_area() {
	if ( ( ! is_front_page() && ! is_home() ) || ! function_exists( 'get_field' ) ) { return ''; }
	$services = new WP_Query( array( 'post_type' => 'servicos', 'post_status' => 'publish', 'posts_per_page' => 40, 'orderby' => 'date', 'order' => 'ASC', 'no_found_rows' => true ) );
	$items = '';
	foreach ( $services->posts as $service ) {
		$url = get_field( 'link_serviço', $service->ID );
		$image = get_field( 'imagem_servico', $service->ID );
		if ( ! is_string( $url ) || ! $url || ! is_numeric( $image ) ) { continue; }
		$items .= '<a class="highlight-card" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . wp_get_attachment_image( (int) $image, 'medium', false, array( 'alt' => $service->post_title ) ) . '</a>';
	}
	return $items ? '<section aria-label="Serviços"><h2>Serviços</h2><div class="highlight-grid">' . $items . '</div></section>' : '';
}
