<?php
/** Banners configuráveis para categorias e demais páginas. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function fafar_banner_default() {
	$banner = get_option( 'fafar_banner_default', false );
	if ( false === $banner ) {
		return array( 'image_url' => get_stylesheet_directory_uri() . '/assets/img/fafar-vista-aerea.jpeg', 'title' => get_bloginfo( 'name' ), 'subtitle' => 'Ensino, pesquisa e extensão' );
	}
	return is_array( $banner ) ? $banner : array();
}

function fafar_banner_category() {
	$category = institutional_fafar_get_category();
	if ( ! $category && ! is_home() && is_singular() ) {
		$alias = get_the_author_meta( 'user_nicename', get_queried_object()->post_author );
		$category = get_category_by_slug( $alias );
	}
	return $category;
}

function get_custom_banner() {
	if ( is_search() || is_404() ) { return; }
	$banner = fafar_banner_default();
	$category = fafar_banner_category();
	if ( $category ) {
		$custom = get_term_meta( $category->term_id, '_fafar_banner', true );
		if ( is_array( $custom ) && isset( $custom['mode'] ) ) {
			if ( 'none' === $custom['mode'] ) { return; }
			if ( 'custom' === $custom['mode'] ) { $banner = $custom; }
		}
	}
	$image = ! empty( $banner['image_id'] ) ? wp_get_attachment_image_url( (int) $banner['image_id'], 'full' ) : ( $banner['image_url'] ?? '' );
	if ( ! $image ) { return; }
	$title = $banner['title'] ?? '';
	$subtitle = $banner['subtitle'] ?? '';
	?>
	<div class="site-container"><div class="page-banner">
		<img src="<?php echo esc_url( $image ); ?>" alt="" width="1200" height="360" fetchpriority="high">
		<?php if ( $title || $subtitle ) : ?><div class="banner-content">
			<?php if ( $subtitle ) : ?><p class="banner-label"><?php echo esc_html( $subtitle ); ?></p><?php endif; ?>
			<?php if ( $title ) : ?>
				<?php if ( is_category() ) : ?><h1 class="banner-title"><?php echo esc_html( $title ); ?></h1><?php else : ?><p class="banner-title"><?php echo esc_html( $title ); ?></p><?php endif; ?>
			<?php endif; ?>
		</div><?php endif; ?>
	</div></div>
	<?php
}

add_action( 'admin_menu', function () {
	add_theme_page( 'Banners', 'Banners', 'manage_options', 'fafar-banners', 'fafar_banner_admin_page' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'appearance_page_fafar-banners' === $hook ) { wp_enqueue_media(); }
} );

add_action( 'admin_post_fafar_save_banner', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Acesso negado.' ) ); }
	check_admin_referer( 'fafar_save_banner' );
	$term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
	if ( $term_id && ! term_exists( $term_id, 'category' ) ) { wp_die( 'Categoria inválida.' ); }
	$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'default';
	if ( ! $term_id ) { $mode = 'custom'; }
	if ( ! in_array( $mode, array( 'default', 'custom', 'none' ), true ) ) { wp_die( 'Opção inválida.' ); }
	$previous = $term_id ? get_term_meta( $term_id, '_fafar_banner', true ) : array();
	$banner = is_array( $previous ) ? $previous : array();
	$banner['mode'] = $mode;
	if ( 'custom' === $mode ) {
		$id = isset( $_POST['image_id'] ) ? absint( $_POST['image_id'] ) : 0;
		// The initial bundled image can be retained until an image is selected in the media library.
		$existing = $term_id ? get_term_meta( $term_id, '_fafar_banner', true ) : fafar_banner_default();
		if ( $id && wp_attachment_is_image( $id ) ) {
			$banner['image_id'] = $id;
			unset( $banner['image_url'] );
		} elseif ( ! $id && ! $term_id && ! empty( $existing['image_url'] ) ) {
			$banner['image_url'] = $existing['image_url'];
		} else {
			wp_safe_redirect( add_query_arg( array( 'page' => 'fafar-banners', 'category' => $term_id, 'banner_error' => 'image' ), admin_url( 'themes.php' ) ) );
			exit;
		}
		$banner['title'] = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$banner['subtitle'] = isset( $_POST['subtitle'] ) ? sanitize_text_field( wp_unslash( $_POST['subtitle'] ) ) : '';
	}
	if ( $term_id ) { update_term_meta( $term_id, '_fafar_banner', $banner ); } else { update_option( 'fafar_banner_default', $banner ); }
	wp_safe_redirect( add_query_arg( array( 'page' => 'fafar-banners', 'category' => $term_id, 'banner_saved' => 1 ), admin_url( 'themes.php' ) ) );
	exit;
} );

function fafar_banner_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$term_id = isset( $_GET['category'] ) ? absint( $_GET['category'] ) : 0;
	$categories = get_categories( array( 'hide_empty' => false ) );
	$term = $term_id ? get_term( $term_id, 'category' ) : null;
	if ( $term_id && ( ! $term || is_wp_error( $term ) ) ) { wp_die( 'Categoria inválida.' ); }
	$banner = $term_id ? get_term_meta( $term_id, '_fafar_banner', true ) : fafar_banner_default();
	$banner = is_array( $banner ) ? $banner : array();
	$mode = $term_id ? ( $banner['mode'] ?? 'default' ) : 'custom';
	$image_id = (int) ( $banner['image_id'] ?? 0 );
	$image = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : ( $banner['image_url'] ?? '' );
	?>
	<div class="wrap"><h1>Banners</h1>
		<?php if ( isset( $_GET['banner_saved'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Banner salvo.</p></div><?php endif; ?>
		<?php if ( isset( $_GET['banner_error'] ) ) : ?><div class="notice notice-error"><p>Selecione uma imagem da biblioteca de mídia para salvar o banner.</p></div><?php endif; ?>
		<form method="get" action="<?php echo esc_url( admin_url( 'themes.php' ) ); ?>">
			<input type="hidden" name="page" value="fafar-banners">
			<label for="banner-category">Configurar:</label>
			<select name="category" id="banner-category"><option value="0">Banner padrão</option>
				<?php foreach ( $categories as $cat ) : ?><option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $term_id, $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?></option><?php endforeach; ?>
			</select> <?php submit_button( 'Abrir', 'secondary', '', false ); ?>
		</form>
		<h2><?php echo $term ? esc_html( $term->name ) : 'Banner padrão'; ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="fafar_save_banner"><input type="hidden" name="term_id" value="<?php echo esc_attr( $term_id ); ?>">
			<?php wp_nonce_field( 'fafar_save_banner' ); ?>
			<?php if ( $term_id ) : ?><fieldset><legend>Exibição</legend>
				<label><input type="radio" name="mode" value="default" <?php checked( $mode, 'default' ); ?>> Usar banner padrão</label><br>
				<label><input type="radio" name="mode" value="custom" <?php checked( $mode, 'custom' ); ?>> Usar banner próprio</label><br>
				<label><input type="radio" name="mode" value="none" <?php checked( $mode, 'none' ); ?>> Não usar banner</label>
			</fieldset><?php endif; ?>
			<div id="banner-fields"><table class="form-table" role="presentation">
				<tr><th scope="row">Imagem <span aria-hidden="true">*</span></th><td>
					<input type="hidden" id="banner-image-id" name="image_id" value="<?php echo esc_attr( $image_id ); ?>">
					<img id="banner-preview" src="<?php echo esc_url( $image ); ?>" alt="" style="display:<?php echo $image ? 'block' : 'none'; ?>;max-width:400px;height:auto;margin-bottom:10px">
					<button type="button" class="button" id="banner-select-image">Selecionar imagem</button><p class="description">Obrigatória. Escolha uma imagem na biblioteca de mídia.</p>
				</td></tr>
				<tr><th scope="row"><label for="banner-title">Título</label></th><td><input class="regular-text" id="banner-title" name="title" value="<?php echo esc_attr( $banner['title'] ?? '' ); ?>"></td></tr>
				<tr><th scope="row"><label for="banner-subtitle">Subtítulo</label></th><td><input class="regular-text" id="banner-subtitle" name="subtitle" value="<?php echo esc_attr( $banner['subtitle'] ?? '' ); ?>"></td></tr>
			</table></div>
			<?php submit_button( 'Salvar banner' ); ?>
		</form>
	</div>
	<script>
	(function($) {
		function toggleFields() { var mode = $('input[name="mode"]:checked').val(); $('#banner-fields').toggle(!mode || mode === 'custom'); }
		$('input[name="mode"]').on('change', toggleFields); toggleFields();
		$('#banner-select-image').on('click', function() {
			var frame = wp.media({title: 'Selecionar imagem do banner', button: {text: 'Usar imagem'}, library: {type: 'image'}, multiple: false});
			frame.on('select', function() { var image = frame.state().get('selection').first().toJSON(); $('#banner-image-id').val(image.id); $('#banner-preview').attr('src', image.url).show(); });
			frame.open();
		});
	})(jQuery);
	</script>
	<?php
}
