<?php
/** Páginas dos programas de pós-graduação. */
/**
 * Cria as páginas do PPGCF caso elas ainda não existam.
 */
function create_ppgcf_pages_if_missing() {
	$ppgcf_user = get_user_by( 'login', 'ppgcf' );

	// Não cria as páginas sem conseguir associá-las ao dono correto.
	if ( ! $ppgcf_user ) {
		return;
	}

	$pages = array(
		'inscricao-ppgcf'       => 'Inscrição PPGCF',
		'candidatos-ppgcf'      => 'Candidatos PPGCF',
		'inscricao-ppgcf-teste' => 'Inscrição PPGCF Teste',
	);

	foreach ( $pages as $slug => $title ) {
		if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
			continue;
		}

		wp_insert_post(
			array(
				'post_author' => $ppgcf_user->ID,
				'post_name'   => $slug,
				'post_title'  => $title,
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
	}
}
add_action( 'after_setup_theme', 'create_ppgcf_pages_if_missing' );

/**
 * Cria as páginas do PPGACT caso elas ainda não existam.
 */
function create_ppgact_pages_if_missing() {
	$ppgact_user = get_user_by( 'login', 'ppgact' );

	// Não cria as páginas sem conseguir associá-las ao dono correto.
	if ( ! $ppgact_user ) {
		return;
	}

	$pages = array(
		'inscricao-ppgact'       => 'Inscrição PPGACT',
		'candidatos-ppgact'      => 'Candidatos PPGACT',
		'inscricao-ppgact-teste' => 'Inscrição PPGACT Teste',
	);

	foreach ( $pages as $slug => $title ) {
		if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
			continue;
		}

		wp_insert_post(
			array(
				'post_author' => $ppgact_user->ID,
				'post_name'   => $slug,
				'post_title'  => $title,
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
	}
}
add_action( 'after_setup_theme', 'create_ppgact_pages_if_missing' );
