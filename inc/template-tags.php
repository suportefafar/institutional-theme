<?php
/** Funções de apresentação do tema. */
/** Only real menus: never silently substitute a list of all pages. */
function fafar_menu( $args ) {
	return wp_nav_menu( array_merge( array( 'container' => false, 'fallback_cb' => false, 'echo' => false ), $args ) );
}

/** Small inline Lucide-compatible stroke icons; no icon font or runtime library. */
function fafar_icon( $name ) {
	$paths = array(
		'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
		'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
		'arrow-right' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
	);
	return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

/** Avoid broken images when the database references uploads that are absent from this installation. */
function fafar_image_url( $url, $fallback ) {
	$uploads = wp_get_upload_dir();
	$path = wp_parse_url( $url, PHP_URL_PATH );
	$base = wp_parse_url( $uploads['baseurl'], PHP_URL_PATH );
	if ( $path && $base && 0 === strpos( $path, $base . '/' ) ) {
		$file = $uploads['basedir'] . substr( $path, strlen( $base ) );
		if ( ! is_file( $file ) ) { return $fallback; }
	}
	return $url;
}

/** Return generated editorial art for the selected homepage posts without changing WordPress media records. */
function fafar_home_post_art_url( $post_id = 0 ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();
	$art = array(
		50717 => 'cemed-pergunta.webp',
		50708 => 'ppgact-doutorado-resultado.webp',
		50703 => 'ppgact-bolsas-resultado.webp',
	);
	if ( ! isset( $art[ $post_id ] ) ) { return ''; }
	return get_stylesheet_directory_uri() . '/assets/img/home-posts/' . $art[ $post_id ];
}

/** Preserve the configured logo, with the institutional asset available locally as a fallback. */
function fafar_site_logo() {
	$fallback = get_stylesheet_directory_uri() . '/assets/img/logo-fafar.png';
	$logo_id = get_theme_mod( 'custom_logo' );
	$url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
	if ( $url && fafar_image_url( $url, $fallback ) !== $fallback ) { the_custom_logo(); return; }
	echo '<a class="custom-logo-link" href="' . esc_url( home_url( '/' ) ) . '" rel="home"><img class="custom-logo" src="' . esc_url( $fallback ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" width="80" height="80"></a>';
}

/** Use the bundled institutional footer logo when its upload is absent in development. */
add_filter( 'render_block_core/image', function ( $html ) {
	$tag = new WP_HTML_Tag_Processor( $html );
	if ( ! $tag->next_tag( 'img' ) ) { return $html; }
	$src = $tag->get_attribute( 'src' );
	if ( ! is_string( $src ) || 'logo-ufmg-rodape.png' !== basename( (string) wp_parse_url( $src, PHP_URL_PATH ) ) ) { return $html; }
	$url = fafar_image_url( $src, get_stylesheet_directory_uri() . '/assets/img/logo-ufmg.png' );
	if ( $url !== $src ) {
		$tag->set_attribute( 'src', $url );
		$tag->remove_attribute( 'srcset' );
		$tag->remove_attribute( 'sizes' );
	}
	return $tag->get_updated_html();
} );
