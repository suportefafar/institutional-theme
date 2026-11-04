<?php
get_header();
if ( is_singular() ) {
	while ( have_posts() ) {
		the_post();
		get_template_part( 'template-parts/content', 'single' );
		if ( fafar_cemed_comments_allowed( get_the_ID() ) ) { comments_template(); }
	}
} else {
	?>
	<header class="listing-header">
		<p class="eyebrow"><?php echo is_search() ? 'Busca no site' : 'Acontece na FAFAR'; ?></p>
		<h1><?php
		if ( is_search() ) { echo esc_html( 'Resultados para “' . get_search_query() . '”' ); }
		elseif ( is_archive() ) { the_archive_title(); }
		else { echo 'Notícias e comunicados'; }
		?></h1>
		<?php if ( is_archive() ) { the_archive_description( '<div class="archive-description">', '</div>' ); } ?>
	</header>
	<?php if ( have_posts() ) : ?>
		<div class="post-list"><?php while ( have_posts() ) { the_post(); get_template_part( 'template-parts/content', 'card' ); } ?></div>
		<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '← Anterior', 'next_text' => 'Próxima →' ) ); ?>
	<?php else : ?>
		<div class="empty-state ffds-card"><h2>Nenhum resultado encontrado</h2><p>Tente buscar por outro termo ou navegue pelo menu da seção.</p><?php get_search_form(); ?></div>
	<?php endif;
}
get_footer();
