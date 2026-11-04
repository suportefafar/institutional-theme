<?php
/** Metadados e indexação das páginas institucionais. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( defined( 'FAFAR_SEO_LOADED' ) ) { return; }
define( 'FAFAR_SEO_LOADED', true );

if ( ! function_exists( 'fafar_seo_excluded_pages' ) ) {
	function fafar_seo_excluded_pages() {
		return array( 'pagina-de-teste', 'inscricao-teste', 'inscricao-ppgcf-teste', 'inscricao-ppgact-teste', 'candidatos-ppgcf', 'candidatos-ppgact', 'gerenciar-perguntas-cemed', 'feedback-pergunta-cemed' );
	}
}

add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ( 'page' !== $post_type ) { return $args; }
	$excluded = isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array();
	foreach ( fafar_seo_excluded_pages() as $path ) {
		$page = get_page_by_path( $path, OBJECT, 'page' );
		if ( $page ) { $excluded[] = (int) $page->ID; }
	}
	$args['post__not_in'] = array_values( array_unique( $excluded ) );
	return $args;
}, 10, 2 );

add_filter( 'wp_robots', function ( $robots ) {
	if ( is_page( fafar_seo_excluded_pages() ) ) {
		unset( $robots['index'] );
		$robots['noindex'] = true;
	}
	return $robots;
} );

/** Mantém títulos curtos cadastrados para navegação, mas contextualiza o resultado de busca. */
add_filter( 'document_title_parts', function ( $parts ) {
	$titles = array(
		'apresentacao-ppgact' => 'Apresentação do PPG em Análises Clínicas e Toxicológicas',
		'selecao-ppgact'      => 'Seleção do PPG em Análises Clínicas e Toxicológicas',
		'linhas-de-pesquisa-ppgact' => 'Linhas de pesquisa do PPG em Análises Clínicas e Toxicológicas',
	);
	if ( is_page() ) {
		$page = get_queried_object();
		if ( $page instanceof WP_Post && isset( $titles[ $page->post_name ] ) ) { $parts['title'] = $titles[ $page->post_name ]; }
	}
	return $parts;
} );

add_action( 'wp_head', function () {
	// Um plugin especializado deve ser a única fonte de meta descriptions, quando presente.
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) ) { return; }
	$description = '';
	if ( is_front_page() ) {
		$description = 'Faculdade de Farmácia da UFMG: ensino, pesquisa, extensão, serviços e notícias em Belo Horizonte.';
	} elseif ( is_singular() && ! is_page( fafar_seo_excluded_pages() ) ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post && ! post_password_required( $post ) ) {
			$description = trim( (string) get_post_meta( $post->ID, '_fafar_meta_description', true ) );
			if ( ! $description ) {
				$text = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
				$text = strip_shortcodes( $text );
				$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text, true ) ) );
				if ( $text ) { $description = wp_html_excerpt( $text, 160, '…' ); }
			}
		}
	}
	if ( $description ) { echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n"; }
}, 1 );

/** Atualiza somente links internos legados, preservando caminhos e parâmetros. */
if ( ! function_exists( 'fafar_https_internal_links' ) ) {
	function fafar_https_internal_links( $html ) {
		return str_replace( 'http://www.farmacia.ufmg.br/', 'https://www.farmacia.ufmg.br/', $html );
	}
}
add_filter( 'the_content', 'fafar_https_internal_links', 20 );
add_filter( 'the_excerpt', 'fafar_https_internal_links', 20 );
add_filter( 'wp_nav_menu_objects', function ( $items ) {
	foreach ( $items as $item ) {
		if ( isset( $item->url ) ) { $item->url = fafar_https_internal_links( $item->url ); }
	}
	return $items;
} );

/** Os serviços e o rodapé obtêm URLs diretamente dos campos e itens do WordPress. */
if ( ! function_exists( 'fafar_seo_service_url' ) ) {
	function fafar_seo_service_url( $url ) {
		if ( ! is_string( $url ) ) { return $url; }
		if ( preg_match( '~^https?://www\.farmacia\.ufmg\.br/farmaciajr/?$~i', $url ) ) {
			return 'https://www.farmaciajr.com/';
		}
		return fafar_https_internal_links( $url );
	}
}
add_filter( 'acf/format_value/name=link_serviço', 'fafar_seo_service_url' );
add_filter( 'wp_get_nav_menu_items', function ( $items ) {
	if ( ! is_array( $items ) ) { return $items; }
	foreach ( $items as $item ) {
		if ( isset( $item->url ) ) { $item->url = fafar_seo_service_url( $item->url ); }
	}
	return $items;
} );
// Também cobre URLs de serviços e banners legados impressas pelo tema com esc_url().
add_filter( 'clean_url', 'fafar_seo_service_url', 10, 1 );
