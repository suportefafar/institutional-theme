<?php
/** Perguntas públicas ao Cemed e consulta autenticada das submissões. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FAFAR_CEMED_MANAGE_CAPABILITY', 'manage_cemed_questions' );

add_action( 'init', 'fafar_cemed_register_post_type' );
add_action( 'init', 'fafar_cemed_create_pages', 20 );
add_action( 'template_redirect', 'fafar_cemed_redirect_old_management_url', 1 );
add_action( 'template_redirect', 'fafar_cemed_protect_submissions_page' );
add_action( 'fafar_cemed_retry_notification', 'fafar_cemed_retry_notification', 10, 2 );
add_action( 'wp_ajax_fafar_cemed_form_nonce', 'fafar_cemed_refresh_form_nonce' );
add_action( 'wp_ajax_nopriv_fafar_cemed_form_nonce', 'fafar_cemed_refresh_form_nonce' );
add_filter( 'comments_open', 'fafar_cemed_keep_answer_comments_open', PHP_INT_MAX, 2 );
add_filter( 'pings_open', '__return_false', PHP_INT_MAX );
add_filter( 'pre_option_comment_registration', 'fafar_cemed_allow_guest_answer_comments' );
add_filter( 'notify_post_author', 'fafar_cemed_enable_comment_notification', 10, 2 );
add_filter( 'notify_moderator', 'fafar_cemed_enable_moderation_notification', 10, 2 );
add_filter( 'comment_notification_recipients', 'fafar_cemed_comment_notification_recipients', 10, 2 );
add_filter( 'comment_moderation_recipients', 'fafar_cemed_comment_notification_recipients', 10, 2 );

/** Reconhece apenas os posts públicos gerados por uma resposta do Cemed. */
function fafar_cemed_is_answer_post( $post_id ) {
	$post_id = absint( $post_id );
	if ( ! $post_id ) {
		return false;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		return false;
	}
	$question_id = absint( get_post_meta( $post->ID, '_cemed_question_id', true ) );
	return $question_id && 'cemed_pergunta' === get_post_type( $question_id );
}

/** Somente posts publicados do Cemed recebem comentários no site. */
function fafar_cemed_comments_allowed( $post_id ) {
	$post = get_post( absint( $post_id ) );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) { return false; }
	return fafar_cemed_is_answer_post( $post->ID ) || has_category( 'cemed', $post->ID );
}

/** Aplica a política também às submissões diretas, independentemente do status salvo. */
function fafar_cemed_keep_answer_comments_open( $open, $post_id ) {
	return fafar_cemed_comments_allowed( $post_id );
}

/** Permite visitantes comentarem somente nas respostas públicas do Cemed. */
function fafar_cemed_allow_guest_answer_comments( $pre_option ) {
	$post_id = is_singular( 'post' ) ? get_queried_object_id() : 0;
	if ( ! $post_id && isset( $_POST['comment_post_ID'] ) && is_scalar( $_POST['comment_post_ID'] ) ) {
		$post_id = absint( wp_unslash( $_POST['comment_post_ID'] ) );
	}
	return fafar_cemed_comments_allowed( $post_id ) ? '0' : $pre_option;
}

/** Reconhece comentários de visitantes nos posts públicos de respostas do Cemed. */
function fafar_cemed_comment_targets_notification( $comment_id ) {
	$comment = get_comment( absint( $comment_id ) );
	if ( ! $comment || ! in_array( $comment->comment_type, array( '', 'comment' ), true ) ) {
		return false;
	}
	return fafar_cemed_email_delivery_enabled() && fafar_cemed_is_answer_post( $comment->comment_post_ID );
}

/** Garante o aviso de comentários publicados, mesmo se a opção global estiver desligada. */
function fafar_cemed_enable_comment_notification( $notify, $comment_id ) {
	return fafar_cemed_comment_targets_notification( $comment_id ) ? true : $notify;
}

/** Garante o aviso de moderação somente para comentários que realmente estão pendentes. */
function fafar_cemed_enable_moderation_notification( $notify, $comment_id ) {
	$comment = get_comment( absint( $comment_id ) );
	if ( $comment && '0' === (string) $comment->comment_approved && fafar_cemed_comment_targets_notification( $comment_id ) ) {
		return true;
	}
	return $notify;
}

/** Inclui o Cemed sem substituir nem duplicar os destinatários padrão do WordPress. */
function fafar_cemed_comment_notification_recipients( $emails, $comment_id ) {
	if ( ! is_array( $emails ) || ! fafar_cemed_comment_targets_notification( $comment_id ) ) {
		return $emails;
	}
	$recipient = 'sim.cemedufmg@gmail.com';
	foreach ( $emails as $email ) {
		if ( 0 === strcasecmp( trim( (string) $email ), $recipient ) ) {
			return $emails;
		}
	}
	$emails[] = $recipient;
	return $emails;
}

/** Fornece um nonce atual para páginas públicas que ficaram abertas. */
function fafar_cemed_refresh_form_nonce() {
	nocache_headers();
	wp_send_json_success( array( 'nonce' => wp_create_nonce( 'fafar_cemed_submit' ) ) );
}

function fafar_cemed_register_post_type() {
	register_post_type(
		'cemed_pergunta',
		array(
			'labels'              => array( 'name' => 'Perguntas Cemed', 'singular_name' => 'Pergunta Cemed' ),
			'public'              => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_rest'        => false,
			'supports'            => array( 'title', 'editor' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		)
	);
}

/** Migra a página antiga e cria apenas as URLs ausentes. */
function fafar_cemed_create_pages() {
	$old_page = get_page_by_path( 'perguntas-cemed', OBJECT, 'page' );
	$new_page = get_page_by_path( 'gerenciar-perguntas-cemed', OBJECT, 'page' );
	if ( $old_page && ! $new_page ) {
		wp_update_post( array( 'ID' => $old_page->ID, 'post_name' => 'gerenciar-perguntas-cemed', 'post_title' => 'Gerenciar Perguntas Cemed' ) );
	}
	foreach ( array( 'pergunte-ao-cemed' => 'Pergunte ao Cemed', 'gerenciar-perguntas-cemed' => 'Gerenciar Perguntas Cemed', 'feedback-pergunta-cemed' => 'Avaliação da Resposta Cemed' ) as $slug => $title ) {
		if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
			continue;
		}
		wp_insert_post(
			array(
				'post_name'   => $slug,
				'post_title'  => $title,
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
	}
}

function fafar_cemed_redirect_old_management_url() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( 'perguntas-cemed' === trim( (string) $path, '/' ) ) {
		wp_safe_redirect( home_url( '/gerenciar-perguntas-cemed/' ), 301 );
		exit;
	}
}

function fafar_cemed_protect_submissions_page() {
	if ( ! is_page( 'gerenciar-perguntas-cemed' ) ) {
		return;
	}
	nocache_headers();
	if ( ! is_user_logged_in() ) {
		auth_redirect();
		exit;
	}
	if ( ! current_user_can( FAFAR_CEMED_MANAGE_CAPABILITY ) ) { wp_die( 'Acesso negado.', '', array( 'response' => 403 ) ); }
}

/** Lê somente valores escalares, evitando arrays injetados nos campos. */
function fafar_cemed_post_value( $key ) {
	if ( ! isset( $_POST[ $key ] ) || ! is_scalar( $_POST[ $key ] ) ) {
		return '';
	}
	return trim( (string) wp_unslash( $_POST[ $key ] ) );
}

function fafar_cemed_post_values( $key ) {
	if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) {
		return array();
	}
	$values = array();
	foreach ( wp_unslash( $_POST[ $key ] ) as $value ) {
		if ( is_scalar( $value ) ) {
			$values[] = sanitize_text_field( trim( (string) $value ) );
		}
	}
	return array_values( array_unique( $values ) );
}

function fafar_cemed_patient_disease_options() {
	return array( 'Câncer', 'Colesterol elevado', 'Depressão', 'Diabetes (tipo I, tipo II e gestacional)', 'Insuficiência hepática', 'Insuficiência renal', 'Hipertensão arterial sistêmica', 'Osteoporose', 'Problema cardíaco (ex.: insuficiência cardíaca congestiva, arritmias, angina, histórico de infarto agudo do miocárdio, entre outras)', 'Problema respiratório (ex.: asma, doença pulmonar obstrutiva crônica, entre outras)', 'Nenhuma', 'Outro' );
}

function fafar_cemed_patient_special_condition_options() {
	return array( 'Gravidez', 'Lactação', 'Menopausa', 'Nenhuma', 'Outro' );
}

function fafar_cemed_answer_classification_options() {
	return array( 'Armazenamento / estabilidade', 'Registro / Equivalência e bioequivalência - Genérico, similar e referência', 'Farmacocinética / Farmacodinâmica', 'Farmacotécnica / Tecnologia farmacêutica / Incompatibilidades', 'Indicações', 'Interações medicamentosas: medicamento-medicamento, medicamento-alimento ou medicamento-álcool', 'Legislação / políticas de saúde / Acesso aos medicamentos', 'Posologia / vias de administração', 'Precauções / contraindicações / alergias', 'Reações adversas', 'Toxicidade', 'Eficácia / Efetividade', 'Outro' );
}

function fafar_cemed_additional_info_options() {
	return array( 'Nenhuma', 'Medicamentos', 'Posologia (dose/frequência)', 'Doenças', 'Resultados de exames laboratoriais', 'Outro' );
}

function fafar_cemed_form_values() {
	$fields = array( 'email', 'nome', 'cpf', 'telefone', 'uf', 'municipio', 'profissional_saude', 'profissao_saude', 'profissao_saude_outro', 'idade_paciente', 'sexo_paciente', 'genero_paciente', 'doencas_preexistentes_outro', 'condicoes_especiais_outro', 'pergunta', 'paciente_especifico', 'informacoes_adicionais', 'consentimento' );
	$values = array();
	foreach ( $fields as $field ) {
		$values[ $field ] = fafar_cemed_post_value( $field );
	}
	foreach ( array( 'doencas_preexistentes', 'condicoes_especiais' ) as $field ) {
		$values[ $field ] = fafar_cemed_post_values( $field );
	}
	return $values;
}

/** Valida no servidor, grava a pergunta e redireciona após sucesso. */
function fafar_cemed_handle_submission( $values ) {
	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || 'fafar_cemed_submit' !== fafar_cemed_post_value( 'fafar_cemed_action' ) ) {
		return null;
	}

	$nonce = fafar_cemed_post_value( 'fafar_cemed_nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'fafar_cemed_submit' ) ) {
		return new WP_Error( 'nonce', 'A sessão expirou. Seus dados foram mantidos; clique em Enviar pergunta novamente.' );
	}
	if ( '' !== fafar_cemed_post_value( 'website' ) ) {
		return new WP_Error( 'spam', 'Não foi possível enviar a pergunta.' );
	}
	foreach ( array( 'nome' => 'nome', 'email' => 'e-mail', 'pergunta' => 'pergunta' ) as $field => $label ) {
		if ( '' === $values[ $field ] ) {
			return new WP_Error( 'required', 'Preencha o campo ' . $label . '.' );
		}
	}
	if ( ! is_email( $values['email'] ) ) {
		return new WP_Error( 'email', 'Informe um e-mail válido.' );
	}
	foreach ( array( 'profissional_saude', 'paciente_especifico', 'consentimento' ) as $field ) {
		if ( ! in_array( $values[ $field ], array( 'Sim', 'Não' ), true ) ) {
			return new WP_Error( 'choice', 'Selecione uma opção para todos os campos de Sim ou Não.' );
		}
	}
	$professions = array( 'Dentista', 'Enfermeiro', 'Farmacêutico', 'Médico', 'Outro' );
	if ( 'Sim' === $values['profissional_saude'] && ! in_array( $values['profissao_saude'], $professions, true ) ) {
		return new WP_Error( 'profession', 'Selecione sua profissão.' );
	}
	if ( 'Sim' === $values['profissional_saude'] && 'Outro' === $values['profissao_saude'] && '' === $values['profissao_saude_outro'] ) {
		return new WP_Error( 'profession_other', 'Informe sua profissão.' );
	}
	if ( 'Sim' === $values['paciente_especifico'] ) {
		if ( ! preg_match( '/^(?:0|[1-9]\d{0,2})$/', $values['idade_paciente'] ) || absint( $values['idade_paciente'] ) > 130 ) {
			return new WP_Error( 'patient_age', 'Informe uma idade válida para o paciente (de 0 a 130 anos).' );
		}
		if ( ! in_array( $values['sexo_paciente'], array( 'Feminino', 'Masculino' ), true ) ) {
			return new WP_Error( 'patient_sex', 'Selecione o sexo do paciente.' );
		}
		if ( ! in_array( $values['genero_paciente'], array( 'Cisgênero', 'Transgênero', 'Não Binário', 'Prefiro Não Responder' ), true ) ) {
			return new WP_Error( 'patient_gender', 'Selecione o gênero do paciente.' );
		}
		foreach ( array( 'doencas_preexistentes' => fafar_cemed_patient_disease_options(), 'condicoes_especiais' => fafar_cemed_patient_special_condition_options() ) as $field => $allowed ) {
			$choices = array_values( array_intersect( $values[ $field ], $allowed ) );
			if ( ! $choices || count( $choices ) !== count( $values[ $field ] ) ) {
				return new WP_Error( 'patient_conditions', 'Selecione opções válidas para as condições do paciente.' );
			}
			if ( in_array( 'Nenhuma', $choices, true ) && 1 !== count( $choices ) ) {
				return new WP_Error( 'patient_none', '“Nenhuma” não pode ser marcada junto com outras condições.' );
			}
			$other_field = 'doencas_preexistentes' === $field ? 'doencas_preexistentes_outro' : 'condicoes_especiais_outro';
			if ( in_array( 'Outro', $choices, true ) && '' === $values[ $other_field ] ) {
				return new WP_Error( 'patient_other', 'Descreva a outra condição do paciente.' );
			}
		}
	}
	if ( $values['uf'] && ! array_key_exists( $values['uf'], fafar_ppgcf_brazilian_states() ) ) {
		return new WP_Error( 'uf', 'Selecione uma UF válida.' );
	}
	$cpf_digits = preg_replace( '/\D+/', '', $values['cpf'] );
	$phone_digits = preg_replace( '/\D+/', '', $values['telefone'] );
	if ( $values['cpf'] && 11 !== strlen( $cpf_digits ) ) {
		return new WP_Error( 'cpf', 'Informe um CPF com 11 dígitos ou deixe o campo vazio.' );
	}
	if ( $values['telefone'] && ! in_array( strlen( $phone_digits ), array( 10, 11 ), true ) ) {
		return new WP_Error( 'telefone', 'Informe um telefone com DDD e 10 ou 11 dígitos, ou deixe o campo vazio.' );
	}
	$limits = array( 'email' => 254, 'nome' => 200, 'cpf' => 20, 'telefone' => 30, 'uf' => 2, 'municipio' => 120, 'profissao_saude_outro' => 120, 'doencas_preexistentes_outro' => 300, 'condicoes_especiais_outro' => 300, 'pergunta' => 10000, 'informacoes_adicionais' => 10000 );
	foreach ( $limits as $field => $limit ) {
		if ( strlen( $values[ $field ] ) > $limit * 4 ) {
			return new WP_Error( 'length', 'Um dos campos excede o tamanho permitido.' );
		}
	}

	$data = array(
		'email'                  => sanitize_email( $values['email'] ),
		'nome'                   => sanitize_text_field( $values['nome'] ),
		'cpf'                    => $cpf_digits,
		'telefone'               => $phone_digits ? '(' . substr( $phone_digits, 0, 2 ) . ') ' . substr( $phone_digits, 2, -4 ) . '-' . substr( $phone_digits, -4 ) : '',
		'uf'                     => sanitize_text_field( $values['uf'] ),
		'municipio'              => sanitize_text_field( $values['municipio'] ),
		'profissional_saude'     => $values['profissional_saude'],
		'profissao_saude'        => 'Sim' === $values['profissional_saude'] ? $values['profissao_saude'] : '',
		'profissao_saude_outro'  => 'Sim' === $values['profissional_saude'] && 'Outro' === $values['profissao_saude'] ? sanitize_text_field( $values['profissao_saude_outro'] ) : '',
		'paciente_especifico'    => $values['paciente_especifico'],
		'idade_paciente'         => 'Sim' === $values['paciente_especifico'] ? absint( $values['idade_paciente'] ) : '',
		'sexo_paciente'          => 'Sim' === $values['paciente_especifico'] ? $values['sexo_paciente'] : '',
		'genero_paciente'        => 'Sim' === $values['paciente_especifico'] ? $values['genero_paciente'] : '',
		'doencas_preexistentes'  => 'Sim' === $values['paciente_especifico'] ? array_values( array_intersect( $values['doencas_preexistentes'], fafar_cemed_patient_disease_options() ) ) : array(),
		'doencas_preexistentes_outro' => 'Sim' === $values['paciente_especifico'] && in_array( 'Outro', $values['doencas_preexistentes'], true ) ? sanitize_text_field( $values['doencas_preexistentes_outro'] ) : '',
		'condicoes_especiais'    => 'Sim' === $values['paciente_especifico'] ? array_values( array_intersect( $values['condicoes_especiais'], fafar_cemed_patient_special_condition_options() ) ) : array(),
		'condicoes_especiais_outro' => 'Sim' === $values['paciente_especifico'] && in_array( 'Outro', $values['condicoes_especiais'], true ) ? sanitize_text_field( $values['condicoes_especiais_outro'] ) : '',
		'informacoes_adicionais' => sanitize_textarea_field( $values['informacoes_adicionais'] ),
		'consentimento'          => $values['consentimento'],
		'consentimento_em'       => current_time( 'mysql' ),
	);
	$question = sanitize_textarea_field( $values['pergunta'] );
	if ( ! $question || ! $data['nome'] ) {
		return new WP_Error( 'required', 'Preencha o nome e a pergunta.' );
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'cemed_pergunta',
			'post_status'  => 'publish',
			'post_title'   => 'Pergunta Cemed — ' . current_time( 'd/m/Y H:i' ),
			'post_content' => $question,
		),
		true
	);
	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return new WP_Error( 'save', 'Não foi possível registrar a pergunta. Tente novamente.' );
	}
	$created_at = get_post_time( 'U', true, $post_id );
	$code = 'CEMEDUFMG-' . absint( $created_at ?: time() );
	if ( false === update_post_meta( $post_id, '_cemed_data', $data ) || false === update_post_meta( $post_id, '_cemed_status', 'nova' ) || false === update_post_meta( $post_id, '_cemed_code', $code ) ) {
		wp_delete_post( $post_id, true );
		return new WP_Error( 'save', 'Não foi possível registrar a pergunta. Tente novamente.' );
	}
	wp_update_post( array( 'ID' => $post_id, 'post_title' => 'Pergunta Cemed — ' . $code ) );
	fafar_cemed_dispatch_notifications( $post_id );

	wp_safe_redirect( add_query_arg( array( 'enviada' => '1', 'referencia' => $code ), get_permalink() ), 303 );
	exit;
}

/** Atribui o código baseado na data original também a perguntas já existentes. */
function fafar_cemed_question_code( $post_id ) {
	$code = get_post_meta( $post_id, '_cemed_code', true );
	if ( is_string( $code ) && preg_match( '/^CEMEDUFMG-\d{10,}$/', $code ) ) {
		return $code;
	}
	$created_at = get_post_time( 'U', true, $post_id );
	$code = 'CEMEDUFMG-' . absint( $created_at ?: time() );
	update_post_meta( $post_id, '_cemed_code', $code );
	return $code;
}

/** Destinatários configurados fora do tema, no ambiente do WordPress. */
function fafar_cemed_notification_recipients() {
	$raw = getenv( 'CEMED_NOTIFICATION_EMAILS' );
	if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
		return array();
	}
	$addresses = array_map( 'trim', explode( ',', $raw ) );
	$addresses = array_filter( $addresses, 'is_email' );
	return array_values( array_unique( $addresses ) );
}

/** O dev nunca envia mensagens aos endereços reais configurados no .env. */
function fafar_cemed_email_delivery_enabled() {
	return 'production' === wp_get_environment_type();
}

function fafar_cemed_notification_meta_key( $type ) {
	return in_array( $type, array( 'requester', 'team', 'response', 'direct_response' ), true ) ? '_cemed_email_' . $type : '';
}

/** O e-mail da equipe contém um link à página protegida, sem dados de saúde. */
function fafar_cemed_build_notification( $post_id, $type ) {
	$post = get_post( $post_id );
	$data = get_post_meta( $post_id, '_cemed_data', true );
	if ( ! $post || 'cemed_pergunta' !== $post->post_type || ! is_array( $data ) || empty( $data['email'] ) || ! is_email( $data['email'] ) ) {
		return new WP_Error( 'invalid_question', 'Os dados da pergunta não permitem montar o e-mail.' );
	}
	$contact = getenv( 'CEMED_CONTACT_EMAIL' );
	$contact = is_string( $contact ) && is_email( $contact ) ? $contact : '';
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( $contact ) {
		$headers[] = 'Reply-To: ' . $contact;
	}
	$footer = $contact ? '<p>Em caso de dúvidas, escreva para <a href="mailto:' . esc_attr( $contact ) . '">' . esc_html( $contact ) . '</a>.</p>' : '';
	$code = fafar_cemed_question_code( $post_id );
	if ( 'requester' === $type ) {
		$body = '<p>Olá, ' . esc_html( $data['nome'] ?? '' ) . '.</p><p>Recebemos sua pergunta ao Serviço de Informação sobre Medicamentos do Cemed/UFMG.</p><p>Número de referência: <strong>' . esc_html( $code ) . '</strong>.</p><p>Nossa equipe analisará a solicitação.</p>' . $footer;
		return array( 'to' => array( $data['email'] ), 'subject' => 'Cemed/UFMG — confirmação da pergunta ' . $code, 'body' => $body, 'headers' => $headers );
	}
	if ( 'team' === $type ) {
		$recipients = fafar_cemed_notification_recipients();
		if ( ! $recipients ) {
			return new WP_Error( 'missing_recipients', 'Os destinatários do Cemed não estão configurados.' );
		}
		$url  = home_url( '/gerenciar-perguntas-cemed/' );
		$body = '<p>Uma nova pergunta foi recebida pelo Cemed/UFMG.</p><p>Número de referência: <strong>' . esc_html( $code ) . '</strong>.</p><p>Solicitante: ' . esc_html( $data['nome'] ?? '—' ) . '.</p><p><a href="' . esc_url( $url ) . '">Acessar a página protegida de perguntas</a></p>';
		return array( 'to' => $recipients, 'subject' => 'Cemed/UFMG — nova pergunta ' . $code, 'body' => $body, 'headers' => $headers );
	}
	if ( 'response' === $type ) {
		$public_post_id = absint( get_post_meta( $post_id, '_cemed_public_post_id', true ) );
		$token = get_post_meta( $post_id, '_cemed_feedback_token', true );
		if ( ! $public_post_id || ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
			return new WP_Error( 'missing_response', 'A resposta publicada não está disponível.' );
		}
		$post_url = get_permalink( $public_post_id );
		$feedback_url = add_query_arg( 'codigo', $token, home_url( '/feedback-pergunta-cemed/' ) );
		$body = '<p>Olá, ' . esc_html( $data['nome'] ?? '' ) . '.</p><p>Sua pergunta <strong>' . esc_html( $code ) . '</strong> foi respondida pelo Cemed/UFMG.</p><p><a href="' . esc_url( $post_url ) . '">Leia a pergunta e a resposta</a>.</p><p><a href="' . esc_url( $feedback_url ) . '">Avalie a resposta recebida</a>.</p>' . $footer;
		return array( 'to' => array( $data['email'] ), 'subject' => 'Cemed/UFMG — sua pergunta foi respondida: ' . $code, 'body' => $body, 'headers' => $headers );
	}
	if ( 'direct_response' === $type ) {
		$question = get_post_meta( $post_id, '_cemed_public_question', true );
		$question = is_string( $question ) && '' !== trim( $question ) ? $question : $post->post_content;
		$answer = get_post_meta( $post_id, '_cemed_resposta', true );
		if ( ! is_string( $answer ) || '' === trim( $answer ) ) {
			return new WP_Error( 'missing_response', 'A resposta não está disponível.' );
		}
		$body = '<p>Olá, ' . esc_html( $data['nome'] ?? '' ) . '.</p><p>Segue a resposta do Cemed/UFMG à sua pergunta <strong>' . esc_html( $code ) . '</strong>:</p>' . fafar_cemed_public_answer_content( $question, $answer ) . $footer;
		return array( 'to' => array( $data['email'] ), 'subject' => 'Cemed/UFMG — resposta à sua pergunta ' . $code, 'body' => $body, 'headers' => $headers );
	}
	return new WP_Error( 'invalid_type', 'Tipo de notificação inválido.' );
}

/** Envia uma vez por tipo, registra falhas e agenda até quatro novas tentativas. */
function fafar_cemed_process_notification( $post_id, $type ) {
	$meta_key = fafar_cemed_notification_meta_key( $type );
	if ( ! $meta_key ) {
		return;
	}
	$state = get_post_meta( $post_id, $meta_key, true );
	$state = is_array( $state ) ? $state : array();
	if ( 'sent' === ( $state['status'] ?? '' ) ) {
		return;
	}
	if ( ! fafar_cemed_email_delivery_enabled() ) {
		update_post_meta( $post_id, $meta_key, array( 'status' => 'suppressed_non_production', 'updated_at' => current_time( 'mysql' ) ) );
		return;
	}
	$attempts = isset( $state['attempts'] ) ? absint( $state['attempts'] ) + 1 : 1;
	$payload  = fafar_cemed_build_notification( $post_id, $type );
	$sent     = ! is_wp_error( $payload ) && wp_mail( $payload['to'], $payload['subject'], $payload['body'], $payload['headers'] );
	$state    = array( 'status' => $sent ? 'sent' : 'failed', 'attempts' => $attempts, 'updated_at' => current_time( 'mysql' ) );
	update_post_meta( $post_id, $meta_key, $state );
	if ( $sent ) {
		return;
	}
	error_log( sprintf( '[Cemed] Email delivery failed for question #%d (%s).', absint( $post_id ), $type ) );
	if ( $attempts >= 5 ) {
		return;
	}
	$args = array( absint( $post_id ), $type );
	if ( ! wp_next_scheduled( 'fafar_cemed_retry_notification', $args ) ) {
		$delays = array( 5 * MINUTE_IN_SECONDS, 15 * MINUTE_IN_SECONDS, HOUR_IN_SECONDS, 4 * HOUR_IN_SECONDS );
		wp_schedule_single_event( time() + $delays[ $attempts - 1 ], 'fafar_cemed_retry_notification', $args );
	}
}

function fafar_cemed_retry_notification( $post_id, $type ) {
	if ( fafar_cemed_email_delivery_enabled() ) {
		fafar_cemed_process_notification( absint( $post_id ), $type );
	}
}

function fafar_cemed_dispatch_notifications( $post_id ) {
	foreach ( array( 'requester', 'team' ) as $type ) {
		fafar_cemed_process_notification( $post_id, $type );
	}
}

/** Prepara apenas a pergunta revisada e a resposta para o post público. */
function fafar_cemed_public_answer_content( $question, $answer ) {
	return '<h2>Pergunta</h2>' . wp_kses_post( wpautop( $question ) ) . '<h2>Resposta</h2>' . wp_kses_post( wpautop( $answer ) );
}

/** Publica uma vez; alterações posteriores atualizam o mesmo post. */
function fafar_cemed_publish_answer( $question_id, $public_title, $public_question, $answer ) {
	$category = term_exists( 'cemed', 'category' );
	if ( ! $category ) {
		$category = wp_insert_term( 'cemed', 'category', array( 'slug' => 'cemed' ) );
	}
	if ( is_wp_error( $category ) ) {
		return new WP_Error( 'category', 'Não foi possível preparar a categoria cemed.' );
	}
	$category_id = is_array( $category ) ? absint( $category['term_id'] ) : absint( $category );
	if ( ! $category_id ) {
		return new WP_Error( 'category', 'A categoria cemed não está disponível.' );
	}

	$existing_id = absint( get_post_meta( $question_id, '_cemed_public_post_id', true ) );
	if ( $existing_id ) {
		$existing = get_post( $existing_id );
		if ( ! $existing || 'post' !== $existing->post_type ) {
			return new WP_Error( 'public_post', 'O post público associado à pergunta não está disponível.' );
		}
	}
	$payload = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'post_title'     => $public_title,
		'post_content'   => fafar_cemed_public_answer_content( $public_question, $answer ),
		'post_category'  => array( $category_id ),
		'comment_status' => 'open',
		'ping_status'    => 'closed',
	);
	if ( $existing_id ) {
		$payload['ID'] = $existing_id;
		$public_post_id = wp_update_post( $payload, true );
	} else {
		$payload['post_author'] = get_current_user_id();
		$public_post_id = wp_insert_post( $payload, true );
	}
	if ( is_wp_error( $public_post_id ) || ! $public_post_id ) {
		return new WP_Error( 'publish', 'Não foi possível publicar a resposta. Tente novamente.' );
	}
	if ( ! $existing_id ) {
		try {
			$token = hash( 'sha256', random_bytes( 32 ) );
		} catch ( Exception $exception ) {
			wp_delete_post( $public_post_id, true );
			return new WP_Error( 'token', 'Não foi possível criar o link de avaliação.' );
		}
		if ( false === update_post_meta( $question_id, '_cemed_public_post_id', $public_post_id ) || false === update_post_meta( $question_id, '_cemed_feedback_token', $token ) || false === update_post_meta( $public_post_id, '_cemed_question_id', $question_id ) ) {
			wp_delete_post( $public_post_id, true );
			delete_post_meta( $question_id, '_cemed_public_post_id' );
			delete_post_meta( $question_id, '_cemed_feedback_token' );
			return new WP_Error( 'save', 'Não foi possível associar o post à pergunta.' );
		}
	}
	return array( 'post_id' => $public_post_id, 'created' => ! $existing_id );
}

/** Remove apenas o post público, preservando a pergunta para revisão ou republicação. */
function fafar_cemed_delete_public_post() {
	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || 'delete_public_post' !== fafar_cemed_post_value( 'fafar_cemed_manage_action' ) ) {
		return null;
	}
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'forbidden', 'Sua conta não pode excluir posts.' );
	}

	$question_id    = absint( fafar_cemed_post_value( 'cemed_question_id' ) );
	$public_post_id = absint( fafar_cemed_post_value( 'cemed_public_post_id' ) );
	$question       = $question_id ? get_post( $question_id ) : null;
	$public_post    = $public_post_id ? get_post( $public_post_id ) : null;
	$associated_id  = $question_id ? absint( get_post_meta( $question_id, '_cemed_public_post_id', true ) ) : 0;

	if ( ! $question || 'cemed_pergunta' !== $question->post_type || 'publish' !== $question->post_status || ! $public_post || 'post' !== $public_post->post_type || $associated_id !== $public_post_id ) {
		return new WP_Error( 'invalid_post', 'O post selecionado não está associado a esta pergunta.' );
	}
	if ( is_admin() ? ! current_user_can( FAFAR_CEMED_MANAGE_CAPABILITY ) : ! current_user_can( 'delete_post', $public_post_id ) ) {
		return new WP_Error( 'forbidden', 'Sua conta não tem permissão para excluir este post.' );
	}
	$nonce = fafar_cemed_post_value( 'fafar_cemed_delete_nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'fafar_cemed_delete_public_post_' . $question_id . '_' . $public_post_id ) ) {
		return new WP_Error( 'nonce', 'A sessão expirou. Atualize a página e tente novamente.' );
	}
	if ( ! wp_trash_post( $public_post_id ) ) {
		return new WP_Error( 'delete', 'Não foi possível excluir o post. Tente novamente.' );
	}
	if ( ! delete_post_meta( $question_id, '_cemed_public_post_id' ) ) {
		wp_untrash_post( $public_post_id );
		return new WP_Error( 'delete', 'Não foi possível remover a associação. O post foi restaurado.' );
	}

	delete_post_meta( $question_id, '_cemed_feedback_token' );
	delete_post_meta( $public_post_id, '_cemed_question_id' );
	update_post_meta( $question_id, '_cemed_status', 'em_analise' );
	update_post_meta( $question_id, '_cemed_atualizada_em', current_time( 'mysql' ) );
	update_post_meta( $question_id, '_cemed_atualizada_por', get_current_user_id() );

	$page_number = max( 1, absint( fafar_cemed_post_value( 'cemed_page_number' ) ) );
	wp_safe_redirect( add_query_arg( array( 'excluida' => $question_id, 'confirmacao' => wp_create_nonce( 'fafar_cemed_deleted_' . $question_id ), 'pagina' => $page_number ), fafar_cemed_management_url() ) . '#cemed-pergunta-' . $question_id, 303 );
	exit;
}

/** Salva o acompanhamento; responder publica o post e notifica o solicitante. */
function fafar_cemed_manage_question() {
	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || 'save' !== fafar_cemed_post_value( 'fafar_cemed_manage_action' ) ) {
		return null;
	}
	if ( ! is_user_logged_in() || ! current_user_can( FAFAR_CEMED_MANAGE_CAPABILITY ) ) {
		return new WP_Error( 'forbidden', 'Sua conta não pode gerenciar perguntas.' );
	}
	$post_id = absint( fafar_cemed_post_value( 'cemed_question_id' ) );
	$post    = $post_id ? get_post( $post_id ) : null;
	if ( ! $post || 'cemed_pergunta' !== $post->post_type || 'publish' !== $post->post_status ) {
		return new WP_Error( 'invalid_post', 'A pergunta selecionada não está disponível.' );
	}
	$nonce = fafar_cemed_post_value( 'fafar_cemed_manage_nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'fafar_cemed_manage_' . $post_id ) ) {
		return new WP_Error( 'nonce', 'A sessão expirou. Atualize a página e tente novamente.' );
	}
	$action = fafar_cemed_post_value( 'cemed_action' );
	if ( ! $action ) {
		$action = '1' === fafar_cemed_post_value( 'cemed_publish' ) ? 'publish' : 'draft';
	}
	if ( ! in_array( $action, array( 'draft', 'respond_user', 'publish' ), true ) ) {
		return new WP_Error( 'action', 'Ação inválida.' );
	}
	$answer = fafar_cemed_post_value( 'cemed_resposta' );
	$public_title = fafar_cemed_post_value( 'cemed_public_title' );
	$public_question = fafar_cemed_post_value( 'cemed_public_question' );
	$classifications = fafar_cemed_post_values( 'cemed_classificacao' );
	$classifications_other = fafar_cemed_post_value( 'cemed_classificacao_outro' );
	$additional_needed = fafar_cemed_post_value( 'cemed_informacao_adicional' );
	$additional_types = fafar_cemed_post_values( 'cemed_informacao_adicional_tipo' );
	$additional_types_other = fafar_cemed_post_value( 'cemed_informacao_adicional_outro' );
	$additional_details = fafar_cemed_post_value( 'cemed_informacoes_adicionais_detalhes' );
	if ( strlen( $public_title ) > 800 || strlen( $answer ) > 40000 || strlen( $public_question ) > 40000 ) {
		return new WP_Error( 'length', 'A pergunta ou a resposta excede o tamanho permitido.' );
	}
	$classifications_allowed = fafar_cemed_answer_classification_options();
	if ( count( array_intersect( $classifications, $classifications_allowed ) ) !== count( $classifications ) ) {
		return new WP_Error( 'classification', 'Selecione classificações válidas.' );
	}
	if ( '' !== $additional_needed && ! in_array( $additional_needed, array( 'Sim', 'Não' ), true ) ) {
		return new WP_Error( 'additional_info', 'Informe se foram necessárias informações adicionais.' );
	}
	$additional_allowed = fafar_cemed_additional_info_options();
	if ( count( array_intersect( $additional_types, $additional_allowed ) ) !== count( $additional_types ) ) {
		return new WP_Error( 'additional_info_types', 'Selecione opções válidas para as informações adicionais.' );
	}
	if ( strlen( $classifications_other ) > 1200 || strlen( $additional_types_other ) > 1200 || strlen( $additional_details ) > 10000 ) {
		return new WP_Error( 'length', 'Um dos campos de classificação ou informações adicionais excede o tamanho permitido.' );
	}
	$answer = wp_kses_post( $answer );
	$public_title = sanitize_text_field( $public_title );
	$public_question = wp_kses_post( $public_question );
	$classifications_other = sanitize_text_field( $classifications_other );
	$additional_types_other = sanitize_text_field( $additional_types_other );
	$additional_details = sanitize_textarea_field( $additional_details );
	$public_post_id = absint( get_post_meta( $post_id, '_cemed_public_post_id', true ) );
	if ( 'publish' === $action && ( '' === $public_title || '' === $public_question || '' === $answer ) ) {
		return new WP_Error( 'required', 'Para publicar, preencha o título da publicação, a pergunta revisada e a resposta.' );
	}
	if ( 'respond_user' === $action && '' === $answer ) {
		return new WP_Error( 'required', 'Para responder ao usuário, preencha a resposta.' );
	}
	update_post_meta( $post_id, '_cemed_resposta', $answer );
	update_post_meta( $post_id, '_cemed_public_title', $public_title );
	update_post_meta( $post_id, '_cemed_public_question', $public_question );
	update_post_meta( $post_id, '_cemed_classificacao', $classifications );
	update_post_meta( $post_id, '_cemed_classificacao_outro', $classifications_other );
	update_post_meta( $post_id, '_cemed_informacao_adicional', $additional_needed );
	update_post_meta( $post_id, '_cemed_informacao_adicional_tipo', $additional_types );
	update_post_meta( $post_id, '_cemed_informacao_adicional_outro', $additional_types_other );
	update_post_meta( $post_id, '_cemed_informacoes_adicionais_detalhes', $additional_details );
	if (
		get_post_meta( $post_id, '_cemed_resposta', true ) !== $answer ||
		get_post_meta( $post_id, '_cemed_public_title', true ) !== $public_title ||
		get_post_meta( $post_id, '_cemed_public_question', true ) !== $public_question ||
		get_post_meta( $post_id, '_cemed_classificacao', true ) !== $classifications ||
		get_post_meta( $post_id, '_cemed_classificacao_outro', true ) !== $classifications_other ||
		get_post_meta( $post_id, '_cemed_informacao_adicional', true ) !== $additional_needed ||
		get_post_meta( $post_id, '_cemed_informacao_adicional_tipo', true ) !== $additional_types ||
		get_post_meta( $post_id, '_cemed_informacao_adicional_outro', true ) !== $additional_types_other ||
		get_post_meta( $post_id, '_cemed_informacoes_adicionais_detalhes', true ) !== $additional_details
	) {
		return new WP_Error( 'save', 'Não foi possível salvar o acompanhamento. Tente novamente.' );
	}
	if ( 'publish' === $action ) {
		$publication = fafar_cemed_publish_answer( $post_id, $public_title, $public_question, $answer );
		if ( is_wp_error( $publication ) ) {
			return $publication;
		}
	}
	$current_status = get_post_meta( $post_id, '_cemed_status', true );
	$status = in_array( $action, array( 'respond_user', 'publish' ), true ) ? 'respondida' : ( $public_post_id || 'respondida' === $current_status ? 'respondida' : 'em_analise' );
	update_post_meta( $post_id, '_cemed_status', $status );
	if ( get_post_meta( $post_id, '_cemed_status', true ) !== $status ) {
		return new WP_Error( 'save', 'Não foi possível salvar a situação da pergunta.' );
	}
	update_post_meta( $post_id, '_cemed_atualizada_em', current_time( 'mysql' ) );
	update_post_meta( $post_id, '_cemed_atualizada_por', get_current_user_id() );
	if ( 'publish' === $action ) {
		fafar_cemed_process_notification( $post_id, 'response' );
	} elseif ( 'respond_user' === $action ) {
		delete_post_meta( $post_id, '_cemed_email_direct_response' );
		fafar_cemed_process_notification( $post_id, 'direct_response' );
	}

	$page_number = max( 1, absint( fafar_cemed_post_value( 'cemed_page_number' ) ) );
	wp_safe_redirect( add_query_arg( array( 'atualizada' => $post_id, 'confirmacao' => wp_create_nonce( 'fafar_cemed_updated_' . $post_id ), 'pagina' => $page_number ), fafar_cemed_management_url() ) . '#cemed-pergunta-' . $post_id, 303 );
	exit;
}

/** Localiza a pergunta pelo código não enumerável recebido no link do e-mail. */
function fafar_cemed_feedback_question( $token ) {
	if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
		return null;
	}
	$questions = get_posts( array( 'post_type' => 'cemed_pergunta', 'post_status' => 'publish', 'posts_per_page' => 1, 'meta_key' => '_cemed_feedback_token', 'meta_value' => $token, 'suppress_filters' => false ) );
	if ( ! $questions ) {
		return null;
	}
	$question = $questions[0];
	$stored = get_post_meta( $question->ID, '_cemed_feedback_token', true );
	return is_string( $stored ) && hash_equals( $stored, $token ) ? $question : null;
}

/** Aceita uma única avaliação para cada pergunta respondida. */
function fafar_cemed_handle_feedback( $question, $token ) {
	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || 'submit' !== fafar_cemed_post_value( 'fafar_cemed_feedback_action' ) ) {
		return null;
	}
	if ( ! $question || ! absint( get_post_meta( $question->ID, '_cemed_public_post_id', true ) ) ) {
		return new WP_Error( 'invalid_link', 'O link de avaliação é inválido.' );
	}
	if ( ! wp_verify_nonce( fafar_cemed_post_value( 'fafar_cemed_feedback_nonce' ), 'fafar_cemed_feedback_' . $question->ID ) ) {
		return new WP_Error( 'nonce', 'A sessão expirou. Atualize a página e tente novamente.' );
	}
	if ( get_post_meta( $question->ID, '_cemed_feedback', true ) ) {
		return new WP_Error( 'already_sent', 'Esta resposta já foi avaliada.' );
	}
	$clarified = fafar_cemed_post_value( 'duvida_esclarecida' );
	$language = fafar_cemed_post_value( 'linguagem_clara' );
	$rating = fafar_cemed_post_value( 'nota' );
	$comments = fafar_cemed_post_value( 'comentarios' );
	foreach ( array( $clarified, $language ) as $choice ) {
		if ( ! in_array( $choice, array( 'Sim', 'Não', 'Parcialmente' ), true ) ) {
			return new WP_Error( 'choice', 'Selecione as opções de esclarecimento e linguagem.' );
		}
	}
	if ( ! in_array( $rating, array( '1', '2', '3', '4', '5' ), true ) ) {
		return new WP_Error( 'rating', 'Selecione uma nota de 1 a 5.' );
	}
	if ( strlen( $comments ) > 20000 ) {
		return new WP_Error( 'length', 'Os comentários excedem o tamanho permitido.' );
	}
	$data = array( 'codigo_resposta' => fafar_cemed_question_code( $question->ID ), 'duvida_esclarecida' => $clarified, 'linguagem_clara' => $language, 'nota' => $rating, 'comentarios' => sanitize_textarea_field( $comments ), 'enviado_em' => current_time( 'mysql' ) );
	if ( ! add_post_meta( $question->ID, '_cemed_feedback', $data, true ) ) {
		return new WP_Error( 'save', 'Não foi possível registrar a avaliação. Tente novamente.' );
	}
	wp_safe_redirect( add_query_arg( array( 'codigo' => $token, 'enviado' => '1' ), get_permalink() ), 303 );
	exit;
}

/** Gestão das perguntas no painel administrativo. */
add_action( 'admin_menu', function () {
	add_menu_page( 'Gerenciar perguntas Cemed', 'Gerenciar perguntas Cemed', FAFAR_CEMED_MANAGE_CAPABILITY, 'cemed-perguntas', 'fafar_cemed_admin_page', 'dashicons-format-chat' );
} );
add_action( 'admin_init', function () {
	if ( ! isset( $_GET['page'] ) || 'cemed-perguntas' !== $_GET['page'] || ! current_user_can( FAFAR_CEMED_MANAGE_CAPABILITY ) ) { return; }
	$GLOBALS['fafar_cemed_admin_result'] = fafar_cemed_delete_public_post();
	if ( null === $GLOBALS['fafar_cemed_admin_result'] ) { $GLOBALS['fafar_cemed_admin_result'] = fafar_cemed_manage_question(); }
} );
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_cemed-perguntas' !== $hook ) { return; }
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	wp_enqueue_style( 'fafar-cemed-admin', $uri . '/assets/css/cemed.css', array(), filemtime( $dir . '/assets/css/cemed.css' ) );
	wp_enqueue_style( 'fafar-cemed-admin-overrides', $uri . '/assets/css/cemed-admin.css', array( 'fafar-cemed-admin' ), filemtime( $dir . '/assets/css/cemed-admin.css' ) );
	wp_enqueue_script( 'fafar-cemed-management', $uri . '/assets/js/cemed-management.js', array(), filemtime( $dir . '/assets/js/cemed-management.js' ), true );
	wp_enqueue_editor();
} );
function fafar_cemed_admin_page() {
	if ( ! current_user_can( FAFAR_CEMED_MANAGE_CAPABILITY ) ) { wp_die( 'Acesso negado.' ); }
	$fafar_cemed_admin = true;
	include get_stylesheet_directory() . '/page-gerenciar-perguntas-cemed.php';
}
function fafar_cemed_management_url() {
	if ( is_admin() ) {
		$args = array( 'page' => 'cemed-perguntas' );
		foreach ( array( 'busca', 'de', 'ate', 'pagina', 'pergunta' ) as $key ) {
			if ( isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ) { $args[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); }
		}
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}
	return get_permalink();
}

require_once __DIR__ . '/cemed-report.php';
