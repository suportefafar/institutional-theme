<?php
/** Importação local do histórico Cemed. Uso: php tools/import-cemed-legacy.php /caminho/dados.json */
if ( PHP_SAPI !== 'cli' || $argc < 2 || $argc > 4 || array_diff( array_slice( $argv, 2 ), array( '--production', '--dry-run' ) ) ) { fwrite( STDERR, "Uso: php import-cemed-legacy.php arquivo.json [--production] [--dry-run]\n" ); exit( 1 ); }
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) && ! ( 'production' === wp_get_environment_type() && in_array( '--production', $argv, true ) ) ) { fwrite( STDERR, "Ambiente não autorizado para importação.\n" ); exit( 1 ); }
$input = json_decode( file_get_contents( $argv[1] ), true );
if ( ! is_array( $input ) || empty( $input['sheets']['solicitacoes'] ) || empty( $input['workbook'] ) || ! hash_equals( $input['source_sha256'] ?? '', hash( 'sha256', base64_decode( $input['workbook'], true ) ?: '' ) ) ) { fwrite( STDERR, "Arquivo de entrada inválido.\n" ); exit( 1 ); }
if ( get_option( 'fafar_cemed_legacy_import_v1', false ) ) { fwrite( STDERR, "Este conjunto já foi importado.\n" ); exit( 1 ); }
$rows = $input['sheets']; $questions = $rows['solicitacoes']; $plans = array(); $lookup = array(); $codes = array();
foreach ( $questions as $i => $row ) {
	$stamp = trim( $row[1] ?? '' ); $format = strlen( $stamp ) === 10 ? '!d/m/Y' : '!d/m/Y H:i:s';
	$date = DateTimeImmutable::createFromFormat( $format, $stamp, wp_timezone() );
	$errors = DateTimeImmutable::getLastErrors();
	if ( ! $date || ( $errors && ( $errors['warning_count'] || $errors['error_count'] ) ) ) { fwrite( STDERR, "Data inválida na linha " . ( $i + 2 ) . ".\n" ); exit( 1 ); }
	$code = 'CEMEDUFMG-' . $date->getTimestamp();
	if ( isset( $codes[ $code ] ) || get_posts( array( 'post_type' => 'cemed_pergunta', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_cemed_code', 'meta_value' => $code ) ) ) { fwrite( STDERR, "Código atual duplicado na linha " . ( $i + 2 ) . ".\n" ); exit( 1 ); }
	$codes[ $code ] = true; $legacy = trim( $row[0] ?? '' );
	if ( $legacy !== '' ) { if ( isset( $lookup[ $legacy ] ) ) { fwrite( STDERR, "Código legado duplicado nas solicitações.\n" ); exit( 1 ); } $lookup[ $legacy ] = $i; }
	$plans[$i] = array( 'row' => $row, 'date' => $date, 'legacy' => $legacy, 'code' => $code, 'resposta' => array(), 'email' => array(), 'avaliacao' => array() );
}
$unmatched = array( 'resposta' => array(), 'email' => array(), 'avaliacao' => array() );
foreach ( array( 'resposta' => 1, 'email' => 0, 'avaliacao' => 5 ) as $sheet => $column ) {
	foreach ( $rows[$sheet] as $i => $row ) {
		if ( ! array_filter( $row, 'strlen' ) ) { continue; }
		$key = trim( $row[$column] ?? '' );
		if ( ! isset( $lookup[$key] ) && preg_match( '/\b(CEMEDUFMG-\d+|CEMED-\d+-\d+-\d+)\b/i', $key, $m ) ) { $key = strtoupper( $m[1] ); }
		$record = array( 'linha' => $i + 2, 'valores' => $row );
		if ( '' !== $key && isset( $lookup[$key] ) ) { $plans[$lookup[$key]][$sheet][] = $record; } else { $unmatched[$sheet][] = $record; }
	}
}
if ( in_array( '--dry-run', $argv, true ) ) { printf( "Prévia: %d solicitações; sem vínculo: respostas %d, emails %d, avaliações %d. Nenhum dado gravado.\n", count($plans), count($unmatched['resposta']), count($unmatched['email']), count($unmatched['avaliacao']) ); exit(0); }
global $wpdb;
if ( '1' !== (string) $wpdb->get_var( "SELECT GET_LOCK('fafar_cemed_legacy_import_v1', 0)" ) ) { fwrite( STDERR, "Outra importação está em andamento.\n" ); exit(1); }
if ( get_option( 'fafar_cemed_legacy_import_v1', false ) ) { fwrite( STDERR, "Este conjunto já foi importado.\n" ); exit(1); }
$wpdb->query( 'START TRANSACTION' );
try {
	foreach ( $plans as $plan ) {
		$r = $plan['row']; $date = $plan['date'];
		$prof = trim( $r[8] ?? '' ); $allowed_prof = array( 'Dentista', 'Enfermeiro', 'Farmacêutico', 'Médico' );
		$data = array( 'email' => $r[2] ?? '', 'nome' => $r[3] ?? '', 'telefone' => $r[4] ?? '', 'uf' => $r[5] ?? '', 'municipio' => $r[6] ?? '', 'profissional_saude' => $r[7] ?? '', 'profissao_saude' => in_array( $prof, $allowed_prof, true ) ? $prof : ( $prof ? 'Outro' : '' ), 'profissao_saude_outro' => $prof && ! in_array( $prof, $allowed_prof, true ) ? $prof : '', 'paciente_especifico' => $r[10] ?? '', 'idade_paciente' => $r[11] ?? '', 'sexo_paciente' => $r[12] ?? '', 'genero_paciente' => $r[13] ?? '', 'doencas_preexistentes' => array(), 'condicoes_especiais' => array(), 'informacoes_adicionais' => $r[20] ?? '', 'cpf' => $r[21] ?? '', 'consentimento' => $r[22] ?? '', 'consentimento_em' => '' );
		foreach ( array( 16 => array( 'doencas_preexistentes', fafar_cemed_patient_disease_options() ), 19 => array( 'condicoes_especiais', fafar_cemed_patient_special_condition_options() ) ) as $col => $field ) { if ( in_array( trim( $r[$col] ?? '' ), $field[1], true ) ) { $data[$field[0]] = array( trim( $r[$col] ) ); } }
		$id = wp_insert_post( wp_slash( array( 'post_type' => 'cemed_pergunta', 'post_status' => 'publish', 'post_title' => 'Pergunta Cemed — ' . $plan['code'], 'post_content' => $r[9] ?? '', 'post_date' => $date->format('Y-m-d H:i:s'), 'post_date_gmt' => $date->setTimezone( new DateTimeZone('UTC') )->format('Y-m-d H:i:s') ) ), true );
		if ( is_wp_error( $id ) || ! $id ) { throw new RuntimeException( 'Falha ao criar uma solicitação.' ); }
		$meta = array( '_cemed_data' => $data, '_cemed_code' => $plan['code'], '_cemed_legacy_code' => $plan['legacy'], '_cemed_legacy_history' => array( 'solicitacao' => $r, 'data_original' => $r[1], 'data_sem_horario' => strlen( trim( $r[1] ) ) === 10, 'respostas' => $plan['resposta'], 'emails' => $plan['email'], 'avaliacoes' => $plan['avaliacao'] ) );
		$sent = count( $plan['email'] ) === 1 && 'OK' === trim( $plan['email'][0]['valores'][3] ?? '' );
		$meta['_cemed_status'] = $sent ? 'respondida' : ( $plan['resposta'] ? 'em_analise' : 'nova' );
		if ( count( $plan['resposta'] ) === 1 ) {
			$a = $plan['resposta'][0]['valores']; $meta['_cemed_resposta'] = $a[6] ?? ''; $meta['_cemed_informacao_adicional'] = in_array( $a[3] ?? '', array( 'Sim', 'Não' ), true ) ? $a[3] : '';
			$meta['_cemed_classificacao'] = in_array( $a[2] ?? '', fafar_cemed_answer_classification_options(), true ) ? array( $a[2] ) : array();
			$meta['_cemed_informacao_adicional_tipo'] = in_array( $a[4] ?? '', fafar_cemed_additional_info_options(), true ) ? array( $a[4] ) : array();
			$meta['_cemed_informacoes_adicionais_detalhes'] = $a[5] ?? '';
		}
		if ( $sent ) { $meta['_cemed_email_direct_response'] = array( 'status' => 'sent', 'origem' => 'planilha legada' ); }
		if ( count( $plan['avaliacao'] ) === 1 ) { $v = $plan['avaliacao'][0]['valores']; $meta['_cemed_feedback'] = array( 'codigo_resposta' => $plan['code'], 'duvida_esclarecida' => $v[1] ?? '', 'linguagem_clara' => $v[2] ?? '', 'nota' => $v[3] ?? '', 'comentarios' => $v[4] ?? '', 'enviado_em' => $v[0] ?? '' ); }
		foreach ( $meta as $key => $value ) { update_post_meta( $id, $key, wp_slash( $value ) ); if ( get_post_meta( $id, $key, true ) !== $value ) { throw new RuntimeException( 'Falha ao preservar metadados.' ); } }
	}
	$archive = array( 'headers' => $input['headers'], 'sheets' => $rows, 'unmatched' => $unmatched, 'source_sha256' => $input['source_sha256'], 'imported_at' => current_time('mysql') );
	if ( ! add_option( 'fafar_cemed_legacy_archive_v1', $archive, '', false ) || ! add_option( 'fafar_cemed_legacy_workbook_v1', $input['workbook'], '', false ) || ! add_option( 'fafar_cemed_legacy_import_v1', array( 'count' => count( $plans ), 'source_sha256' => $input['source_sha256'] ), '', false ) ) { throw new RuntimeException( 'Falha ao preservar o arquivo original.' ); }
	$role = get_role( 'administrator' ); if ( ! $role ) { throw new RuntimeException( 'Papel administrador indisponível.' ); } $role->add_cap( 'manage_cemed_questions' );
	$wpdb->query( 'COMMIT' );
	printf("Importadas %d solicitações. Linhas sem vínculo: respostas %d, emails %d, avaliações %d.\n", count($plans), count($unmatched['resposta']), count($unmatched['email']), count($unmatched['avaliacao']));
} catch ( Throwable $e ) { $wpdb->query( 'ROLLBACK' ); fwrite( STDERR, $e->getMessage() . "\n" ); exit(1); }
