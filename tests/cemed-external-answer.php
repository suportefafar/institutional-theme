<?php
/** Integração com WordPress de desenvolvimento; nunca executa em produção. */
if ( empty( $argv[1] ) ) { fwrite( STDERR, "Informe o caminho de wp-load.php em desenvolvimento.\n" ); exit( 1 ); }
require $argv[1];
if ( 'production' === wp_get_environment_type() ) { throw new RuntimeException( 'Este teste só pode executar em desenvolvimento.' ); }
function check_external( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } echo 'PASS: ' . $message . PHP_EOL; }
$id = wp_insert_post( array( 'post_type' => 'cemed_pergunta', 'post_status' => 'publish', 'post_title' => 'Teste sintético de redação externa' ) );
try {
	check_external( (bool) $id, 'pergunta de teste criada' );
	wp_set_current_user( 0 );
	check_external( '' === fafar_cemed_external_answer_url( $id ), 'visitante não cria links' );
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
	wp_set_current_user( $admins[0]->ID );
	$url = fafar_cemed_external_answer_url( $id );
	parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $query );
	$token = $query['codigo'] ?? '';
	check_external( 64 === strlen( $token ), 'equipe cria link com token aleatório' );
	wp_set_current_user( 0 );
	check_external( null === fafar_cemed_external_answer_question( 'inválido' ), 'token inválido rejeitado' );
	check_external( null === fafar_cemed_external_answer_question( str_repeat( 'a', 64 ) ), 'token de outra origem rejeitado' );
	$_POST = array( 'cemed_external_answer' => '<p>Resposta <strong>formatada</strong><img src=x onerror=alert(1)><iframe src=x></iframe></p>' );
	check_external( is_wp_error( fafar_cemed_save_external_answer( $token ) ), 'POST sem nonce rejeitado' );
	$_POST['cemed_external_nonce'] = wp_create_nonce( 'cemed_external_' . $token );
	check_external( true === fafar_cemed_save_external_answer( $token ), 'visitante salva resposta com token e nonce válidos' );
	$draft = get_post_meta( $id, '_cemed_external_answer', true );
	check_external( str_contains( $draft, '<strong>' ) && ! str_contains( $draft, '<img' ) && ! str_contains( $draft, '<iframe' ), 'formatação preservada e HTML perigoso removido' );
	check_external( '' === get_post_meta( $id, '_cemed_resposta', true ), 'visitante não altera a resposta oficial' );
	check_external( $draft === fafar_cemed_answer_for_editor( $id ), 'editor apresenta diretamente a resposta externa' );
	check_external( ! array_key_exists( 'external_token', fafar_cemed_report_question_data( $id ) ), 'relatórios não expõem o token externo' );
	$_POST['cemed_external_answer'] = '<p>Nova versão</p>';
	check_external( true === fafar_cemed_save_external_answer( $token ), 'link permite atualizar a redação' );
	$_POST['cemed_external_answer'] = '';
	check_external( is_wp_error( fafar_cemed_save_external_answer( $token ) ), 'resposta vazia rejeitada' );
	$_POST['cemed_external_answer'] = str_repeat( 'x', 40001 );
	check_external( is_wp_error( fafar_cemed_save_external_answer( $token ) ), 'limite de tamanho aplicado' );
	update_post_meta( $id, '_cemed_status', 'respondida' );
	check_external( null === fafar_cemed_external_answer_question( $token ) && is_wp_error( fafar_cemed_save_external_answer( $token ) ), 'pergunta respondida bloqueia leitura e escrita externas' );
	update_post_meta( $id, '_cemed_status', 'em_analise' );
	update_post_meta( $id, '_cemed_external_closed', 1 );
	check_external( null === fafar_cemed_external_answer_question( $token ), 'reabertura interna não reativa link encerrado' );
	foreach ( array( 'respond_user', 'publish' ) as $action ) {
		delete_post_meta( $id, '_cemed_external_closed' );
		update_post_meta( $id, '_cemed_status', 'em_analise' );
		update_post_meta( $id, '_cemed_data', array( 'nome' => 'Teste', 'email' => 'teste@example.test' ) );
		wp_set_current_user( $admins[0]->ID );
		fafar_cemed_external_answer_url( $id );
		$active_token = get_post_meta( $id, '_cemed_external_token', true );
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST = array( 'fafar_cemed_manage_action' => 'save', 'cemed_question_id' => $id, 'fafar_cemed_manage_nonce' => wp_create_nonce( 'fafar_cemed_manage_' . $id ), 'cemed_action' => $action, 'cemed_resposta' => '<p>Resposta aprovada</p>', 'cemed_public_title' => 'Teste sintético', 'cemed_public_question' => '<p>Pergunta sintética</p>' );
		$stop_redirect = function () { throw new RuntimeException( 'redirect-test' ); };
		add_filter( 'wp_redirect', $stop_redirect );
		try {
			$result = fafar_cemed_manage_question();
			check_external( false, is_wp_error( $result ) ? $result->get_error_message() : 'Redirecionamento esperado' );
		} catch ( RuntimeException $error ) {
			if ( 'redirect-test' !== $error->getMessage() ) { throw $error; }
		} finally { remove_filter( 'wp_redirect', $stop_redirect ); }
		check_external( null === fafar_cemed_external_answer_question( $active_token ) && get_post_meta( $id, '_cemed_external_closed', true ), $action . ' encerra definitivamente o link externo' );
		check_external( '<p>Resposta aprovada</p>' === fafar_cemed_answer_for_editor( $id ), $action . ' mantém no editor a resposta aprovada' );
	}
} finally {
	$published = get_post_meta( $id, '_cemed_public_post_id', true );
	if ( $published ) { wp_delete_post( $published, true ); }
	wp_delete_post( $id, true );
}
