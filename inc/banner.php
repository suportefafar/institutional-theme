<?php
/** Banner compartilhado, sem execução ou assets do Slider Revolution. */
function get_custom_banner() {
	if ( is_search() || is_404() ) { return; }
	global $wpdb;
	$image = get_stylesheet_directory_uri() . '/assets/img/fafar-vista-aerea.jpeg';
	$title = get_bloginfo( 'name' );
	$label = 'Ensino, pesquisa e extensão';
	$banner_images = array(
		'ppgact' => 'ppgact.jpg',
		'alm'    => 'alm.jpg',
		'act'    => 'act.jpg',
		'fas'    => 'fas.jpg',
		'pfa'    => 'pfa2.jpg',
	);
	$category = institutional_fafar_get_category();
	$alias = $category ? $category->slug : '';
	if ( $category && $category->description ) { $title = wp_strip_all_tags( $category->description ); }
	elseif ( is_category() && $category ) { $title = $category->name; }
	if ( ! $alias && is_singular() ) {
		$alias = get_the_author_meta( 'user_nicename', get_queried_object()->post_author );
		$author_category = get_category_by_slug( $alias );
		if ( $author_category && $author_category->description ) { $title = wp_strip_all_tags( $author_category->description ); }
	}
	// Lê banners legados somente se a tabela existir; funciona sem o plugin.
	$table = $wpdb->prefix . 'revslider_sliders';
	if ( $alias && ! isset( $banner_images[ $alias ] ) && ! is_home() && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) {
		$slider_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `{$table}` WHERE alias = %s LIMIT 1", $alias ) );
		if ( $slider_id ) {
			$slides_table = $wpdb->prefix . 'revslider_slides';
			$params = $wpdb->get_var( $wpdb->prepare( "SELECT params FROM `{$slides_table}` WHERE slider_id = %d ORDER BY id DESC LIMIT 1", $slider_id ) );
			$slide = json_decode( (string) $params, true );
			if ( ! empty( $slide['image'] ) && is_string( $slide['image'] ) ) { $image = $slide['image']; }
		}
	}
	if ( isset( $banner_images[ $alias ] ) ) {
		$image = get_stylesheet_directory_uri() . '/assets/img/banners/' . $banner_images[ $alias ];
	}
	?>
	<div class="site-container"><div class="page-banner">
		<img src="<?php echo esc_url( fafar_image_url( $image, get_stylesheet_directory_uri() . '/assets/img/fafar-vista-aerea.jpeg' ) ); ?>" alt="" width="1200" height="360" fetchpriority="high">
		<div class="banner-content">
            <p class="banner-label"><?php echo esc_html( $label ); ?></p>
            <?php if ( is_category() ) : ?>
                <h1 class="banner-title"><?php echo esc_html( $title ); ?></h1>
            <?php else : ?>
                <p class="banner-title"><?php echo esc_html( $title ); ?></p>
            <?php endif; ?>
        </div>
	</div></div>
	<?php
}
