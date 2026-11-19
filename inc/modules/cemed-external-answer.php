<?php
/** Redação externa por link individual, sem conceder acesso ao painel. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function fafar_cemed_external_answer_open( $id ) {
	$post = get_post( $id );
	return $post && 'cemed_pergunta' === $post->post_type && 'publish' === $post->post_status
		&& 'respondida' !== get_post_meta( $id, '_cemed_status', true )
		&& ! get_post_meta( $id, '_cemed_external_closed', true )
		&& ! get_post_meta( $id, '_cemed_public_post_id', true );
}

/** Exibe no editor a redação externa mais recente enquanto o link estiver ativo. */
function fafar_cemed_answer_for_editor( $id ) {
	$external = get_post_meta( $id, '_cemed_external_answer', true );
	return $external && fafar_cemed_external_answer_open( $id ) ? $external : get_post_meta( $id, '_cemed_resposta', true );
}

/** Somente a equipe pode obter ou criar o link. */
function fafar_cemed_external_answer_url( $id ) {
	if ( ! current_user_can( FAFAR_CEMED_MANAGE_CAPABILITY ) || ! fafar_cemed_external_answer_open( $id ) ) { return ''; }
	$token = get_post_meta( $id, '_cemed_external_token', true );
	if ( ! $token ) {
		add_post_meta( $id, '_cemed_external_token', bin2hex( random_bytes( 32 ) ), true );
		$token = get_post_meta( $id, '_cemed_external_token', true );
	}
	return is_string( $token ) && preg_match( '/^[a-f0-9]{64}$/D', $token ) ? add_query_arg( 'codigo', $token, home_url( '/redigir-resposta-cemed/' ) ) : '';
}

function fafar_cemed_external_answer_question( $token ) {
	if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/D', $token ) ) { return null; }
	$ids = get_posts( array( 'post_type' => 'cemed_pergunta', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_cemed_external_token', 'meta_value' => $token ) );
	$id = $ids ? $ids[0] : 0;
	$stored = $id ? get_post_meta( $id, '_cemed_external_token', true ) : '';
	return $id && is_string( $stored ) && hash_equals( $stored, $token ) && fafar_cemed_external_answer_open( $id ) ? get_post( $id ) : null;
}

function fafar_cemed_save_external_answer( $token ) {
	$question = fafar_cemed_external_answer_question( $token );
	if ( ! $question ) { return new WP_Error( 'closed', 'Este link é inválido ou não está mais disponível.' ); }
	if ( ! wp_verify_nonce( fafar_cemed_post_value( 'cemed_external_nonce' ), 'cemed_external_' . $token ) ) {
		return new WP_Error( 'nonce', 'A sessão expirou. Atualize a página e tente novamente.' );
	}
	$answer = fafar_cemed_post_value( 'cemed_external_answer' );
	if ( strlen( $answer ) > 40000 ) { return new WP_Error( 'length', 'A resposta excede o tamanho permitido.' ); }
	$answer = wp_kses( $answer, array( 'p' => array(), 'br' => array(), 'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(), 'blockquote' => array(), 'a' => array( 'href' => true, 'title' => true ) ) );
	if ( '' === trim( html_entity_decode( wp_strip_all_tags( $answer ), ENT_QUOTES, 'UTF-8' ) ) ) { return new WP_Error( 'empty', 'Escreva a resposta antes de salvar.' ); }
	// O visitante nunca altera a resposta aprovada nem dispara publicações ou e-mails.
	update_post_meta( $question->ID, '_cemed_external_answer', wp_slash( $answer ) );
	if ( get_post_meta( $question->ID, '_cemed_external_answer', true ) !== $answer ) { return new WP_Error( 'save', 'Não foi possível salvar. Tente novamente.' ); }
	update_post_meta( $question->ID, '_cemed_external_saved_at', current_time( 'mysql' ) );
	return true;
}
