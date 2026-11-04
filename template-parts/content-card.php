<article id="post-<?php the_ID(); ?>" <?php post_class( 'ffds-card news-card' ); ?>>
	<div class="ffds-card-body">
		<p class="entry-meta"><?php if ( 'post' === get_post_type() ) : ?><span class="category-label"><?php echo get_the_category_list( ' · ' ); ?></span><span aria-hidden="true"> / </span><?php endif; ?><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
		<h2 class="ffds-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<div class="entry-summary"><?php the_excerpt(); ?></div>
		<a class="read-more" href="<?php the_permalink(); ?>">Leia mais<span class="screen-reader-text">: <?php the_title(); ?></span><?php echo fafar_icon( 'arrow-right' ); ?></a>
	</div>
</article>
