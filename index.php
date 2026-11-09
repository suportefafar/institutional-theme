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
		<p class="eyebrow"><?php echo is_search() ? 'Busca no site' : ( is_home() ? 'Faculdade de Farmácia · UFMG' : 'Acontece na FAFAR' ); ?></p>
		<?php if ( ! is_category() ) : ?>
		<h1><?php
		if ( is_search() ) { echo esc_html( 'Resultados para “' . get_search_query() . '”' ); }
		elseif ( is_archive() ) { the_archive_title(); }
		elseif ( is_home() ) { echo 'Ciência, ensino e cuidado em saúde'; }
		else { echo 'Notícias e comunicados'; }
		?></h1>
		<?php if ( is_archive() ) { the_archive_description( '<div class="archive-description">', '</div>' ); } ?>
		<?php endif; ?>
	</header>
	<?php if ( have_posts() ) : ?>
		<?php if ( is_home() && ! is_search() ) : ?>
			<section class="home-featured" aria-label="Notícias em destaque">
				<?php
				$home_feature_index = 0;
				while ( have_posts() && $home_feature_index < 3 ) {
					the_post();
					get_template_part( 'template-parts/content', 0 === $home_feature_index ? 'feature' : 'compact' );
					$home_feature_index++;
				}
				?>
			</section>
			<?php if ( have_posts() ) : ?>
				<section class="home-latest" aria-labelledby="latest-news-title">
					<div class="section-heading"><div><p class="eyebrow">Notícias e comunicados</p><h2 id="latest-news-title">A vida na FAFAR</h2></div></div>
					<div class="post-list post-grid"><?php while ( have_posts() ) { the_post(); get_template_part( 'template-parts/content', 'card' ); } ?></div>
				</section>
			<?php endif; ?>
		<?php else : ?>
			<div class="post-list"><?php while ( have_posts() ) { the_post(); get_template_part( 'template-parts/content', 'card' ); } ?></div>
		<?php endif; ?>
		<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '← Anterior', 'next_text' => 'Próxima →' ) ); ?>
	<?php else : ?>
		<div class="empty-state ffds-card"><h2>Nenhum resultado encontrado</h2><p>Tente buscar por outro termo ou navegue pelo menu da seção.</p><?php get_search_form(); ?></div>
	<?php endif;
}
get_footer();
