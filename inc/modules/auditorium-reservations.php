<?php
/** Reservas do auditório recebidas e encaminhadas à intranet. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'fafar_auditorium_register_submission_type' );
add_action( 'admin_menu', 'fafar_auditorium_register_admin_page' );
add_action( 'wp_ajax_fafar_auditorium_check', 'fafar_auditorium_ajax_check' );
add_action( 'wp_ajax_nopriv_fafar_auditorium_check', 'fafar_auditorium_ajax_check' );
add_action( 'wp_ajax_fafar_auditorium_submit', 'fafar_auditorium_ajax_submit' );
add_action( 'wp_ajax_nopriv_fafar_auditorium_submit', 'fafar_auditorium_ajax_submit' );
add_action( 'admin_post_fafar_auditorium_retry', 'fafar_auditorium_admin_retry' );

function fafar_auditorium_register_submission_type() {
	register_post_type(
		'fafar_aud_submission',
		array(
			'labels' => array( 'name' => 'Submissões do auditório', 'singular_name' => 'Submissão do auditório' ),
			'public' => false,
			'exclude_from_search' => true,
			'show_ui' => false,
			'show_in_rest' => false,
			'supports' => array( 'title', 'author' ),
			'capability_type' => 'post',
			'map_meta_cap' => true,
		)
	);
}

function fafar_auditorium_register_admin_page() {
	add_menu_page( 'Submissões do auditório', 'Reservas do auditório', 'manage_options', 'fafar-auditorium-submissions', 'fafar_auditorium_render_admin_page', 'dashicons-calendar-alt', 27 );
}

function fafar_auditorium_api_url( $suffix = '' ) {
	$url = getenv( 'INTRANET_AUDITORIUM_RESERVATION_URL' );
	if ( ! is_string( $url ) || '' === trim( $url ) ) {
		$url = get_option( 'institutional_theme_auditorium_reservation_url', '' );
	}
	if ( ! is_string( $url ) || ! preg_match( '#^https?://[^\s]+$#', $url ) ) {
		return '';
	}
	return rtrim( $url, '/' ) . $suffix;
}

function fafar_auditorium_api_token() {
	$token = getenv( 'INTRANET_AUDITORIUM_RESERVATION_TOKEN' );
	return is_string( $token ) ? trim( $token ) : '';
}

function fafar_auditorium_api_request( $url, $body, $idempotency_key = '' ) {
	$token = fafar_auditorium_api_token();
	if ( '' === $url || '' === $token ) {
		return new WP_Error( 'integration_not_configured', 'A integração com a intranet não está configurada.' );
	}
	$headers = array(
		'Authorization' => 'Bearer ' . $token,
		'Content-Type'  => 'application/json',
	);
	if ( $idempotency_key ) {
		$headers['Idempotency-Key'] = $idempotency_key;
	}
	return wp_remote_post(
		$url,
		array(
			'headers' => $headers,
			'body' => $body,
			'timeout' => 20,
			'redirection' => 0,
		)
	);
}

function fafar_auditorium_save_state( $post_id, $state ) {
	$delivery_saved = update_post_meta( $post_id, '_fafar_aud_delivery', $state );
	$status_saved = update_post_meta( $post_id, '_fafar_aud_status', $state['status'] );
	return false !== $delivery_saved && false !== $status_saved;
}

function fafar_auditorium_request_data() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'fafar_auditorium_form' ) ) {
		wp_send_json_error( array( 'code' => 'session_expired', 'message' => 'A sessão expirou. Atualize a página e tente novamente.' ), 403 );
	}
	$raw = isset( $_POST['submission'] ) ? wp_unslash( $_POST['submission'] ) : '';
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		wp_send_json_error( array( 'code' => 'invalid_request', 'message' => 'Não foi possível ler os dados do formulário.' ), 400 );
	}
	return $data;
}

function fafar_auditorium_normalize_submission( $data ) {
	$raw_audience = $data['expected_audience'] ?? null;
	if ( ! is_scalar( $raw_audience ) || '' === trim( (string) $raw_audience ) || ! ctype_digit( (string) $raw_audience ) ) {
		return new WP_Error( 'invalid_audience', 'Informe a previsão de público como um número inteiro entre 0 e 192.' );
	}
	$clean = array(
		'applicant_name' => sanitize_text_field( $data['applicant_name'] ?? '' ),
		'applicant_email' => sanitize_email( $data['applicant_email'] ?? '' ),
		'applicant_phone' => sanitize_text_field( $data['applicant_phone'] ?? '' ),
		'description' => sanitize_text_field( $data['description'] ?? '' ),
		'expected_audience' => absint( $raw_audience ),
		'resources' => array(),
		'occurrences' => array(),
	);
	if ( ! isset( $data['resources'] ) || ! is_array( $data['resources'] ) ) {
		return new WP_Error( 'invalid_resources', 'Responda às perguntas sobre os equipamentos.' );
	}
	$resource_keys = array( 'use_musical_instruments', 'use_fafar_notebook', 'use_own_notebook', 'use_internet_access' );
	foreach ( $resource_keys as $key ) {
		$value = isset( $data['resources'][ $key ] ) ? sanitize_text_field( $data['resources'][ $key ] ) : '';
		if ( ! in_array( $value, array( 'Sim', 'Não' ), true ) ) {
			return new WP_Error( 'invalid_option', 'Selecione Sim ou Não para todos os equipamentos.' );
		}
		$clean['resources'][ $key ] = 'Sim' === $value;
	}
	foreach ( array( 'applicant_name', 'applicant_email', 'applicant_phone', 'description' ) as $key ) {
		if ( '' === $clean[ $key ] ) {
			return new WP_Error( 'required_field', 'Preencha todos os campos obrigatórios.' );
		}
	}
	if ( ! is_email( $clean['applicant_email'] ) ) {
		return new WP_Error( 'invalid_email', 'Informe um e-mail válido.' );
	}
	if ( $clean['expected_audience'] < 0 || $clean['expected_audience'] > 192 ) {
		return new WP_Error( 'invalid_audience', 'A previsão de público deve estar entre 0 e 192 pessoas.' );
	}
	$occurrences = fafar_auditorium_normalize_occurrences( $data['occurrences'] ?? array() );
	if ( is_wp_error( $occurrences ) ) {
		return $occurrences;
	}
	$clean['occurrences'] = $occurrences;
	return $clean;
}

function fafar_auditorium_normalize_occurrences( $occurrences ) {
	$occurrences = is_array( $occurrences ) ? $occurrences : array();
	if ( count( $occurrences ) < 1 || count( $occurrences ) > 31 ) {
		return new WP_Error( 'invalid_occurrences', 'Informe entre 1 e 31 datas para o evento.' );
	}
	$today = new DateTimeImmutable( current_time( 'Y-m-d' ), wp_timezone() );
	$latest = $today->modify( '+365 days' );
	$seen = array();
	$normalized = array();
	foreach ( $occurrences as $index => $occurrence ) {
		if ( ! is_array( $occurrence ) ) {
			return new WP_Error( 'invalid_occurrence', sprintf( 'Confira o conjunto %d de data e horário.', $index + 1 ) );
		}
		$date = isset( $occurrence['date'] ) ? sanitize_text_field( $occurrence['date'] ) : '';
		$start = isset( $occurrence['start_time'] ) ? sanitize_text_field( $occurrence['start_time'] ) : '';
		$end = isset( $occurrence['end_time'] ) ? sanitize_text_field( $occurrence['end_time'] ) : '';
		$date_object = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
		$date_errors = DateTimeImmutable::getLastErrors();
		if ( ! $date_object || ( is_array( $date_errors ) && ( $date_errors['warning_count'] || $date_errors['error_count'] ) ) || $date_object->format( 'Y-m-d' ) !== $date || $date_object < $today || $date_object > $latest ) {
			return new WP_Error( 'invalid_date', sprintf( 'A data do conjunto %d deve estar entre hoje e os próximos 365 dias.', $index + 1 ) );
		}
		if ( ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start ) || ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end ) || $start >= $end ) {
			return new WP_Error( 'invalid_time', sprintf( 'Confira os horários do conjunto %d.', $index + 1 ) );
		}
		$signature = $date . '|' . $start . '|' . $end;
		if ( isset( $seen[ $signature ] ) ) {
			return new WP_Error( 'duplicate_occurrence', 'Há datas e horários repetidos no formulário.' );
		}
		$seen[ $signature ] = true;
		$normalized[] = array( 'date' => $date, 'start_time' => $start, 'end_time' => $end );
	}
	return $normalized;
}

function fafar_auditorium_api_payload( $data ) {
	return array(
		'applicant' => array( 'name' => $data['applicant_name'], 'email' => $data['applicant_email'], 'phone' => $data['applicant_phone'] ),
		'event' => array(
			'description' => $data['description'],
			'expected_audience' => $data['expected_audience'],
			'resources' => $data['resources'],
		),
		'occurrences' => array_map(
			static function ( $item ) {
				return array( 'date' => $item['date'], 'start_time' => $item['start_time'], 'end_time' => $item['end_time'] );
			},
			$data['occurrences']
		),
	);
}

function fafar_auditorium_check_availability( $occurrences ) {
	$body = wp_json_encode( array( 'occurrences' => $occurrences ) );
	$response = fafar_auditorium_api_request( fafar_auditorium_api_url( '/availability' ), $body );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = wp_remote_retrieve_response_code( $response );
	$json = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 !== $code || ! is_array( $json ) || ! isset( $json['data']['conflicts'] ) || ! is_array( $json['data']['conflicts'] ) ) {
		return new WP_Error( 'availability_failed', 'Não foi possível confirmar a disponibilidade das datas.' );
	}
	return $json['data']['conflicts'];
}

function fafar_auditorium_ajax_check() {
	$data = fafar_auditorium_request_data();
	$occurrences = fafar_auditorium_normalize_occurrences( $data['occurrences'] ?? array() );
	if ( is_wp_error( $occurrences ) ) {
		wp_send_json_error( array( 'code' => $occurrences->get_error_code(), 'message' => $occurrences->get_error_message() ), 400 );
	}
	$conflicts = fafar_auditorium_check_availability( $occurrences );
	if ( is_wp_error( $conflicts ) ) {
		wp_send_json_error( array( 'code' => $conflicts->get_error_code(), 'message' => $conflicts->get_error_message() ), 503 );
	}
	wp_send_json_success( array( 'available' => empty( $conflicts ), 'conflicts' => $conflicts ) );
}

function fafar_auditorium_ajax_submit() {
	$normalized = fafar_auditorium_normalize_submission( fafar_auditorium_request_data() );
	if ( is_wp_error( $normalized ) ) {
		wp_send_json_error( array( 'code' => $normalized->get_error_code(), 'message' => $normalized->get_error_message() ), 400 );
	}
	$post_id = wp_insert_post(
		array(
			'post_type' => 'fafar_aud_submission',
			'post_status' => 'publish',
			'post_title' => sprintf( 'Reserva de %s — %s', $normalized['applicant_name'], current_time( 'd/m/Y H:i' ) ),
			'post_author' => 0,
		),
		true
	);
	if ( is_wp_error( $post_id ) || ! $post_id ) {
		wp_send_json_error( array( 'code' => 'persistence_error', 'message' => 'Não foi possível registrar a submissão no site.' ), 500 );
	}
	$state = array( 'status' => 'checking_availability', 'updated_at' => current_time( 'mysql' ), 'occurrences' => array() );
	if ( false === update_post_meta( $post_id, '_fafar_aud_data', $normalized ) || false === update_post_meta( $post_id, '_fafar_aud_delivery', $state ) || false === update_post_meta( $post_id, '_fafar_aud_status', $state['status'] ) ) {
		wp_send_json_error( array( 'code' => 'persistence_error', 'message' => 'A submissão foi iniciada, mas não foi possível salvar seus dados.' ), 500 );
	}
	$conflicts = fafar_auditorium_check_availability( $normalized['occurrences'] );
	if ( is_wp_error( $conflicts ) ) {
		$state['status'] = 'failed_availability';
		$state['message'] = $conflicts->get_error_message();
		$state['updated_at'] = current_time( 'mysql' );
		fafar_auditorium_save_state( $post_id, $state );
		wp_send_json_error( array( 'code' => 'availability_failed', 'message' => 'Não foi possível consultar a disponibilidade. Sua solicitação ficou registrada no protocolo ' . $post_id . '.' ), 503 );
	}
	if ( ! empty( $conflicts ) ) {
		$state['status'] = 'unavailable';
		$state['conflicts'] = $conflicts;
		$state['updated_at'] = current_time( 'mysql' );
		fafar_auditorium_save_state( $post_id, $state );
		wp_send_json_error( array( 'code' => 'unavailable', 'message' => 'Uma ou mais datas já estão ocupadas. Ajuste os conjuntos indicados antes de enviar.', 'conflicts' => $conflicts, 'submission_id' => $post_id ), 409 );
	}
	$key = wp_generate_uuid4();
	$payload = fafar_auditorium_api_payload( $normalized );
	$body = wp_json_encode( $payload );
	$state['status'] = 'sending';
	$state['idempotency_key'] = $key;
	$state['request_body'] = $body;
	$state['updated_at'] = current_time( 'mysql' );
	if ( ! fafar_auditorium_save_state( $post_id, $state ) ) {
		wp_send_json_error( array( 'code' => 'persistence_error', 'message' => 'Não foi possível preparar o envio. A submissão permanece registrada no protocolo ' . $post_id . '.' ), 500 );
	}
	$response = fafar_auditorium_api_request( fafar_auditorium_api_url(), $body, $key );
	if ( is_wp_error( $response ) ) {
		$state['status'] = 'retry_required';
		$state['message'] = 'Não foi possível conectar à intranet.';
		$state['updated_at'] = current_time( 'mysql' );
		fafar_auditorium_save_state( $post_id, $state );
		wp_send_json_error( array( 'code' => 'delivery_failed', 'message' => 'Não foi possível concluir o envio. A submissão ficou registrada no protocolo ' . $post_id . '.' ), 503 );
	}
	$http_code = wp_remote_retrieve_response_code( $response );
	$result = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 201 !== $http_code || ! is_array( $result ) || empty( $result['data']['reservations'] ) ) {
		$api_error = $result['error']['code'] ?? 'unexpected_response';
		$state['status'] = 'availability_conflict' === $api_error ? 'unavailable' : ( 'request_in_progress' === $api_error ? 'retry_required' : 'failed' );
		$state['error_code'] = sanitize_key( $api_error );
		$state['request_id'] = sanitize_text_field( $result['request_id'] ?? '' );
		$state['message'] = 'A intranet não aceitou a solicitação.';
		if ( 'unavailable' === $state['status'] ) {
			$state['conflicts'] = array();
			foreach ( $result['error']['details'] ?? array() as $detail ) {
				if ( preg_match( '/occurrences\[(\d+)\]/', $detail['field'] ?? '', $matches ) ) {
					$state['conflicts'][] = array( 'index' => (int) $matches[1], 'message' => sanitize_text_field( $detail['message'] ?? 'Horário indisponível' ) );
				}
			}
		}
		$state['updated_at'] = current_time( 'mysql' );
		fafar_auditorium_save_state( $post_id, $state );
		$is_unavailable = 'unavailable' === $state['status'];
		wp_send_json_error( array( 'code' => $is_unavailable ? 'unavailable' : $api_error, 'message' => $is_unavailable ? 'O horário foi ocupado antes da conclusão do envio. Confira as datas indicadas.' : 'A intranet não confirmou o envio. Sua submissão ficou registrada no protocolo ' . $post_id . '.', 'conflicts' => $state['conflicts'] ?? array() ), $is_unavailable ? 409 : 502 );
	}
	$state['status'] = 'sent';
	$state['reservations'] = $result['data']['reservations'];
	$state['request_id'] = sanitize_text_field( $result['request_id'] ?? '' );
	$state['updated_at'] = current_time( 'mysql' );
	fafar_auditorium_save_state( $post_id, $state );
	wp_send_json_success( array( 'submission_id' => $post_id, 'message' => 'Solicitação enviada com sucesso. Protocolo ' . $post_id . '.' ) );
}

function fafar_auditorium_status_label( $status ) {
	$labels = array(
		'checking_availability' => 'Aguardando processamento',
		'sending' => 'Enviando para a intranet',
		'sent' => 'Enviada com sucesso',
		'failed' => 'Enviada sem sucesso',
		'retry_required' => 'Envio sem confirmação — requer nova tentativa',
		'failed_availability' => 'Falha ao consultar disponibilidade',
		'unavailable' => 'Horário indisponível',
	);
	return $labels[ $status ] ?? 'Estado desconhecido';
}

function fafar_auditorium_admin_retry() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Você não tem permissão para executar esta ação.', 'institutional-theme' ) );
	}
	$post_id = isset( $_GET['submission_id'] ) ? absint( $_GET['submission_id'] ) : 0;
	check_admin_referer( 'fafar_auditorium_retry_' . $post_id );
	$state = get_post_meta( $post_id, '_fafar_aud_delivery', true );
	$state = is_array( $state ) ? $state : array();
	if ( 'fafar_aud_submission' !== get_post_type( $post_id ) || 'retry_required' !== ( $state['status'] ?? '' ) || empty( $state['idempotency_key'] ) || empty( $state['request_body'] ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=fafar-auditorium-submissions' ) );
		exit;
	}
	$state['status'] = 'sending';
	$state['updated_at'] = current_time( 'mysql' );
	fafar_auditorium_save_state( $post_id, $state );
	$response = fafar_auditorium_api_request( fafar_auditorium_api_url(), $state['request_body'], $state['idempotency_key'] );
	if ( ! is_wp_error( $response ) ) {
		$result = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 201 === wp_remote_retrieve_response_code( $response ) && ! empty( $result['data']['reservations'] ) ) {
			$state['status'] = 'sent';
			$state['reservations'] = $result['data']['reservations'];
			$state['request_id'] = sanitize_text_field( $result['request_id'] ?? '' );
		} else {
			$retry_error = $result['error']['code'] ?? 'unexpected_response';
			$state['status'] = 'availability_conflict' === $retry_error ? 'unavailable' : ( 'request_in_progress' === $retry_error ? 'retry_required' : 'failed' );
			$state['error_code'] = sanitize_key( $retry_error );
			$state['request_id'] = sanitize_text_field( $result['request_id'] ?? '' );
			$state['message'] = 'A intranet não confirmou o envio.';
			if ( 'unavailable' === $state['status'] ) {
				$state['conflicts'] = array();
				foreach ( $result['error']['details'] ?? array() as $detail ) {
					if ( preg_match( '/occurrences\[(\d+)\]/', $detail['field'] ?? '', $matches ) ) {
						$state['conflicts'][] = array( 'index' => (int) $matches[1], 'message' => sanitize_text_field( $detail['message'] ?? 'Horário indisponível' ) );
					}
				}
			}
		}
	} else {
		$state['status'] = 'retry_required';
		$state['message'] = 'A tentativa não recebeu resposta da intranet.';
	}
	$state['updated_at'] = current_time( 'mysql' );
	fafar_auditorium_save_state( $post_id, $state );
	wp_safe_redirect( admin_url( 'admin.php?page=fafar-auditorium-submissions' ) );
	exit;
}

function fafar_auditorium_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'institutional-theme' ) );
	}
	$status_filter = isset( $_GET['delivery_status'] ) ? sanitize_key( wp_unslash( $_GET['delivery_status'] ) ) : '';
	$page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$query = new WP_Query(
		array(
			'post_type' => 'fafar_aud_submission',
			'post_status' => 'publish',
			'posts_per_page' => 25,
			'paged' => $page,
			'orderby' => 'date',
			'order' => 'DESC',
			'no_found_rows' => false,
			'meta_query' => $status_filter ? array( array( 'key' => '_fafar_aud_status', 'value' => $status_filter ) ) : array(),
		)
	);
	$statuses = array( 'sent', 'failed', 'retry_required', 'failed_availability', 'unavailable', 'checking_availability', 'sending' );
	?>
	<div class="wrap">
		<h1>Submissões de reserva do auditório</h1>
		<form method="get" style="margin: 1em 0">
			<input type="hidden" name="page" value="fafar-auditorium-submissions">
			<label for="delivery_status">Desfecho</label>
			<select name="delivery_status" id="delivery_status">
				<option value="">Todos</option>
				<?php foreach ( $statuses as $status ) : ?>
					<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status_filter, $status ); ?>><?php echo esc_html( fafar_auditorium_status_label( $status ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( 'Filtrar', 'secondary', '', false ); ?>
		</form>
		<table class="widefat striped">
			<thead><tr><th>Protocolo</th><th>Data</th><th>Solicitante</th><th>Evento</th><th>Desfecho</th><th>Ocorrências</th></tr></thead>
			<tbody>
			<?php
		$shown = 0;
		foreach ( $query->posts as $submission ) :
			$data = get_post_meta( $submission->ID, '_fafar_aud_data', true );
			$delivery = get_post_meta( $submission->ID, '_fafar_aud_delivery', true );
			$data = is_array( $data ) ? $data : array();
			$delivery = is_array( $delivery ) ? $delivery : array();
			$status = $delivery['status'] ?? 'checking_availability';
			$shown++;
			?>
			<tr>
				<td>#<?php echo esc_html( (string) $submission->ID ); ?></td>
				<td><?php echo esc_html( get_the_date( 'd/m/Y H:i', $submission ) ); ?></td>
				<td><?php echo esc_html( $data['applicant_name'] ?? '—' ); ?><br><small><?php echo esc_html( $data['applicant_email'] ?? '' ); ?></small></td>
				<td><?php echo esc_html( $data['description'] ?? '—' ); ?></td>
				<td><?php echo esc_html( fafar_auditorium_status_label( $status ) ); ?><?php if ( ! empty( $delivery['error_code'] ) ) : ?><br><small><?php echo esc_html( $delivery['error_code'] ); ?></small><?php endif; ?><?php if ( ! empty( $delivery['message'] ) ) : ?><br><small><?php echo esc_html( $delivery['message'] ); ?></small><?php endif; ?><?php if ( 'retry_required' === $status ) : ?><br><a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=fafar_auditorium_retry&submission_id=' . $submission->ID ), 'fafar_auditorium_retry_' . $submission->ID ) ); ?>">Tentar novamente com a mesma chave</a><?php endif; ?></td>
				<td>
					<details><summary><?php echo esc_html( (string) count( $data['occurrences'] ?? array() ) ); ?> data(s)</summary>
						<ul>
						<?php foreach ( $data['occurrences'] ?? array() as $index => $occurrence ) : ?>
							<li><?php echo esc_html( ( $occurrence['date'] ?? '' ) . ' — ' . ( $occurrence['start_time'] ?? '' ) . '–' . ( $occurrence['end_time'] ?? '' ) ); ?><?php foreach ( $delivery['conflicts'] ?? array() as $conflict ) : ?><?php if ( (int) ( $conflict['index'] ?? -1 ) === (int) $index ) : ?> — <strong>indisponível: <?php echo esc_html( $conflict['message'] ?? ( isset( $conflict['occupied'] ) ? ( $conflict['occupied']['start_time'] . '–' . $conflict['occupied']['end_time'] ) : 'conflito com reserva existente' ) ); ?></strong><?php endif; ?><?php endforeach; ?><?php if ( isset( $delivery['reservations'][ $index ]['id'] ) ) : ?> — reserva Rails <?php echo esc_html( $delivery['reservations'][ $index ]['id'] ); ?><?php endif; ?></li>
						<?php endforeach; ?>
						</ul>
						<p>Público previsto: <?php echo esc_html( (string) ( $data['expected_audience'] ?? '—' ) ); ?></p>
						<?php if ( ! empty( $data['resources'] ) && is_array( $data['resources'] ) ) : ?><p>Equipamentos: <?php echo esc_html( implode( ' · ', array_map( static function ( $key, $value ) { $labels = array( 'use_musical_instruments' => 'Instrumentos musicais', 'use_fafar_notebook' => 'Notebook da Faculdade', 'use_own_notebook' => 'Notebook próprio', 'use_internet_access' => 'Acesso à internet' ); return ( $labels[ $key ] ?? $key ) . ': ' . ( $value ? 'Sim' : 'Não' ); }, array_keys( $data['resources'] ), $data['resources'] ) ) ); ?></p><?php endif; ?>
						<p>E-mail: <?php echo esc_html( $data['applicant_email'] ?? '' ); ?> · Telefone: <?php echo esc_html( $data['applicant_phone'] ?? '' ); ?></p>
						<?php if ( ! empty( $delivery['request_id'] ) ) : ?><p>Request ID: <?php echo esc_html( $delivery['request_id'] ); ?></p><?php endif; ?>
					</details>
				</td>
			</tr>
		<?php endforeach; ?>
		<?php if ( ! $shown ) : ?><tr><td colspan="6">Nenhuma submissão encontrada.</td></tr><?php endif; ?>
			</tbody>
		</table>
		<?php if ( $query->max_num_pages > 1 ) : ?>
			<div class="tablenav"><div class="tablenav-pages">
				<?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $page, 'total' => $query->max_num_pages, 'add_args' => $status_filter ? array( 'delivery_status' => $status_filter ) : false ) ) ); ?>
			</div></div>
		<?php endif; ?>
	</div>
	<?php
	wp_reset_postdata();
}
