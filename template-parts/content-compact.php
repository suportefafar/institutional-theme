<?php $home_post_art = fafar_home_post_art_url(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'home-compact-card' . ( has_post_thumbnail() || $home_post_art ? '' : ' home-compact-card--text-only' ) ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="home-compact-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?></a>
	<?php elseif ( $home_post_art ) : ?>
		<a class="home-compact-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo esc_url( $home_post_art ); ?>" alt="" width="1536" height="1024" loading="lazy"></a>
	<?php endif; ?>
	<div class="home-compact-body">
		<p class="eyebrow"><?php echo 'post' === get_post_type() ? wp_kses_post( get_the_category_list( ' · ' ) ) : 'Notícia'; ?></p>
		<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<p class="home-feature-meta"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
	</div>
</article>
