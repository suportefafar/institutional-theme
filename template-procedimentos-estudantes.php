<?php
/** Diretório de procedimentos a partir dos menus dos colegiados. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$entries = array();
foreach ( array( 66 => array( 'Documento e Formulários', 'Farmácia' ), 99 => array( 'Apoio ao Aluno', 'Biomedicina' ) ) as $menu_id => $source ) {
	$items = wp_get_nav_menu_items( $menu_id );
	if ( ! is_array( $items ) ) { continue; }
	$parents = array();
	foreach ( $items as $item ) {
		if ( strtolower( remove_accents( trim( $item->title ) ) ) === strtolower( remove_accents( $source[0] ) ) ) { $parents[] = (int) $item->ID; }
	}
	// Percorre todos os níveis abaixo da seção, mesmo que o menu não esteja ordenado.
	$descendants = $parents;
	do {
		$count = count( $descendants );
		foreach ( $items as $item ) {
			if ( in_array( (int) $item->menu_item_parent, $descendants, true ) && ! in_array( (int) $item->ID, $descendants, true ) ) { $descendants[] = (int) $item->ID; }
		}
	} while ( count( $descendants ) > $count );
	foreach ( $items as $item ) {
		if ( ! in_array( (int) $item->ID, $descendants, true ) || in_array( (int) $item->ID, $parents, true ) || empty( $item->url ) || '#' === $item->url || ! empty( $item->_invalid ) ) { continue; }
		$url = $item->url;
		$key = untrailingslashit( strtok( $url, '#' ) );
		if ( ! isset( $entries[ $key ] ) ) { $entries[ $key ] = array( 'title' => $item->title, 'url' => $url, 'courses' => array(), 'keywords' => array() ); }
		// Indexa somente conteúdo publicado local, sem executar shortcodes ou consultar sites externos.
		$post_id = 'post_type' === $item->type ? (int) $item->object_id : url_to_postid( $url );
		$post = $post_id ? get_post( $post_id ) : null;
		if ( $post && 'publish' === $post->post_status && empty( $post->post_password ) ) {
			$content = strip_shortcodes( $post->post_title . ' ' . $post->post_excerpt . ' ' . $post->post_content );
			$content = wp_strip_all_tags( $content, true );
			preg_match_all( '/[\p{L}\p{N}]+/u', $content, $words );
			$entries[ $key ]['keywords'] = array_unique( array_merge( $entries[ $key ]['keywords'], $words[0] ) );
		}
		$entries[ $key ]['courses'][ $source[1] ] = true;
	}
}
usort( $entries, function ( $a, $b ) { return strcasecmp( remove_accents( $a['title'] ), remove_accents( $b['title'] ) ); } );
get_header();
?>
<article class="student-guide student-procedures">
	<a class="student-procedures-back" href="<?php echo esc_url( home_url( '/comece-por-aqui/#vida-academica' ) ); ?>">← Voltar para Comece por aqui</a>
	<header class="student-procedures-header"><p class="student-guide-overline">Vida acadêmica</p><h1>Procedimentos e documentos</h1><p>Encontre orientações e formulários dos cursos de Biomedicina e Farmácia.</p></header>
	<?php if ( $entries ) : ?>
		<label for="student-procedures-search">Pesquisar por assunto ou curso</label>
		<input id="student-procedures-search" type="search" placeholder="Ex.: estágio, trancamento ou Biomedicina" autocomplete="off">
		<p class="student-procedures-status" aria-live="polite"></p>
		<ul class="student-procedures-list">
			<?php foreach ( $entries as $entry ) : ?>
				<li class="student-procedures-item" data-keywords="<?php echo esc_attr( implode( ' ', $entry['keywords'] ) ); ?>"><a href="<?php echo esc_url( $entry['url'] ); ?>"><span class="student-procedures-title"><?php echo esc_html( $entry['title'] ); ?></span><span class="student-procedures-courses"><?php foreach ( array_keys( $entry['courses'] ) as $course ) : ?><span class="student-procedures-badge <?php echo 'Farmácia' === $course ? 'is-farmacia' : 'is-biomedicina'; ?>"><?php echo esc_html( $course ); ?></span><?php endforeach; ?></span><span aria-hidden="true">→</span></a></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?><p>As informações estão temporariamente indisponíveis. Consulte os colegiados para obter orientações.</p><?php endif; ?>
</article>
<?php get_footer();
