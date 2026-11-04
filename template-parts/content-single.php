<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-content' ); ?>>
	<header class="entry-header">
		<?php if ( 'post' === get_post_type() ) : ?><p class="eyebrow"><?php echo get_the_category_list( ' · ' ); ?></p><?php endif; ?>
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		<?php if ( 'post' === get_post_type() ) : ?><p class="entry-meta"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p><?php endif; ?>
	</header>
	<div class="entry-content"><?php do_action( 'fafar_entry_content_before' ); the_content(); wp_link_pages( array( 'before' => '<nav class="page-links" aria-label="Páginas deste conteúdo">', 'after' => '</nav>' ) ); ?></div>
</article>
