<article id="post-<?php the_ID(); ?>" <?php post_class( 'ffds-card news-card' ); ?>>
	<?php $home_post_art = fafar_home_post_art_url(); ?>
	<?php if ( has_post_thumbnail() ) : ?><a class="news-card-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?></a><?php elseif ( $home_post_art ) : ?><a class="news-card-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo esc_url( $home_post_art ); ?>" alt="" width="1536" height="1024" loading="lazy"></a><?php endif; ?>
	<div class="ffds-card-body">
		<p class="entry-meta"><?php if ( 'post' === get_post_type() ) : ?><span class="category-label"><?php echo get_the_category_list( ' · ' ); ?></span><span aria-hidden="true"> / </span><?php endif; ?><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
		<h2 class="ffds-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<div class="entry-summary"><?php the_excerpt(); ?></div>
		<a class="read-more" href="<?php the_permalink(); ?>">Leia mais<span class="screen-reader-text">: <?php the_title(); ?></span><?php echo fafar_icon( 'arrow-right' ); ?></a>
	</div>
</article>
