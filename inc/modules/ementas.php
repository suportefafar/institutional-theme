<?php
/** Cadastro administrativo e armazenamento das ementas. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function fafar_ementas_register_post_type() {
	register_post_type( 'fafar_ementa', array(
		'labels' => array(
			'name' => 'Ementas',
			'singular_name' => 'Ementa',
			'menu_name' => 'Ementas',
			'add_new_item' => 'Adicionar ementa',
			'edit_item' => 'Editar ementa',
			'new_item' => 'Nova ementa',
			'view_item' => 'Ver ementa',
			'search_items' => 'Buscar ementas',
			'not_found' => 'Nenhuma ementa encontrada',
			'not_found_in_trash' => 'Nenhuma ementa na lixeira',
		),
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_rest' => false,
		'publicly_queryable' => false,
		'exclude_from_search' => true,
		'menu_icon' => 'dashicons-media-document',
		'supports' => array( 'title', 'author' ),
		'capability_type' => 'post',
		'map_meta_cap' => true,
	) );
}
add_action( 'init', 'fafar_ementas_register_post_type' );

function fafar_ementas_add_meta_box() {
	add_meta_box( 'fafar-ementa-details', 'Dados da ementa', 'fafar_ementas_render_meta_box', 'fafar_ementa', 'normal', 'high' );
}
add_action( 'add_meta_boxes_fafar_ementa', 'fafar_ementas_add_meta_box' );

function fafar_ementas_render_meta_box( $post ) {
	wp_nonce_field( 'fafar_ementa_save', 'fafar_ementa_nonce' );
	$fields = array(
		'_fafar_ementa_versao' => array( 'Versão curricular', 'text', 'Ex.: Currículo 2020/1' ),
		'_fafar_ementa_codigo' => array( 'Código da disciplina', 'text', 'Ex.: ACT007' ),
		'_fafar_ementa_arquivo' => array( 'Arquivo da ementa (PDF)', 'url', 'Selecione ou informe a URL do PDF' ),
		'_fafar_ementa_codigo_validacao' => array( 'Código de autenticação', 'text', 'Opcional; usado na página Validar ementa' ),
	);
	foreach ( $fields as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		printf( '<p><label for="%1$s"><strong>%2$s</strong></label><br><input class="widefat" type="%3$s" id="%1$s" name="%1$s" value="%4$s" placeholder="%5$s">', esc_attr( $key ), esc_html( $field[0] ), esc_attr( $field[1] ), esc_attr( $value ), esc_attr( $field[2] ) );
		if ( '_fafar_ementa_arquivo' === $key ) {
			echo '<button type="button" class="button fafar-ementa-select-file">Selecionar PDF</button>';
		}
		echo '</p>';
	}
	echo '<p class="description">O título da ementa deve ser o nome da disciplina. Somente ementas publicadas aparecem na página de downloads.</p>';
}

function fafar_ementas_save_meta( $post_id ) {
	if ( ! isset( $_POST['fafar_ementa_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fafar_ementa_nonce'] ) ), 'fafar_ementa_save' ) ) { return; }
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) { return; }
	if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
	$fields = array(
		'_fafar_ementa_versao' => 'sanitize_text_field',
		'_fafar_ementa_codigo' => 'sanitize_text_field',
		'_fafar_ementa_arquivo' => 'esc_url_raw',
		'_fafar_ementa_codigo_validacao' => 'sanitize_text_field',
	);
	foreach ( $fields as $key => $sanitize ) {
		$value = isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? call_user_func( $sanitize, trim( wp_unslash( $_POST[ $key ] ) ) ) : '';
		if ( '' === $value ) { delete_post_meta( $post_id, $key ); }
		else { update_post_meta( $post_id, $key, $value ); }
	}
}
add_action( 'save_post_fafar_ementa', 'fafar_ementas_save_meta' );

function fafar_ementas_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $screen || 'fafar_ementa' !== $screen->post_type ) { return; }
	wp_enqueue_media();
	wp_add_inline_script( 'jquery', 'jQuery(function($){$(".fafar-ementa-select-file").on("click",function(e){e.preventDefault();var picker=wp.media({title:"Selecionar arquivo da ementa",button:{text:"Usar este arquivo"},library:{type:"application/pdf"},multiple:false});picker.on("select",function(){var item=picker.state().get("selection").first().toJSON();$("#_fafar_ementa_arquivo").val(item.url);});picker.open();});});' );
}
add_action( 'admin_enqueue_scripts', 'fafar_ementas_admin_assets' );

function fafar_ementas_admin_columns( $columns ) {
	$columns['fafar_ementa_codigo'] = 'Código';
	$columns['fafar_ementa_versao'] = 'Versão curricular';
	return $columns;
}
add_filter( 'manage_fafar_ementa_posts_columns', 'fafar_ementas_admin_columns' );

function fafar_ementas_admin_column_content( $column, $post_id ) {
	$keys = array( 'fafar_ementa_codigo' => '_fafar_ementa_codigo', 'fafar_ementa_versao' => '_fafar_ementa_versao' );
	if ( isset( $keys[ $column ] ) ) { echo esc_html( get_post_meta( $post_id, $keys[ $column ], true ) ); }
}
add_action( 'manage_fafar_ementa_posts_custom_column', 'fafar_ementas_admin_column_content', 10, 2 );

function fafar_ementas_admin_sortable_columns( $columns ) {
	$columns['fafar_ementa_codigo'] = 'fafar_ementa_codigo';
	$columns['fafar_ementa_versao'] = 'fafar_ementa_versao';
	return $columns;
}
add_filter( 'manage_edit-fafar_ementa_sortable_columns', 'fafar_ementas_admin_sortable_columns' );

function fafar_ementas_admin_orderby( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) { return; }
	$orderby = $query->get( 'orderby' );
	$meta_keys = array( 'fafar_ementa_codigo' => '_fafar_ementa_codigo', 'fafar_ementa_versao' => '_fafar_ementa_versao' );
	if ( isset( $meta_keys[ $orderby ] ) ) {
		$query->set( 'meta_key', $meta_keys[ $orderby ] );
		$query->set( 'orderby', 'meta_value' );
	}
}
add_action( 'pre_get_posts', 'fafar_ementas_admin_orderby' );
