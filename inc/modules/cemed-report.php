<?php
/** Relatórios agregados das perguntas Cemed. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', function () {
	add_submenu_page( 'cemed-perguntas', 'Relatório de perguntas', 'Relatórios', 'manage_options', 'cemed-relatorios', 'fafar_cemed_report_form' );
} );
add_action( 'admin_post_fafar_cemed_report', 'fafar_cemed_report_download' );

function fafar_cemed_report_form() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Acesso negado.' ); }
	?>
	<div class="wrap">
		<h1>Relatório de perguntas Cemed</h1>
		<p>Selecione os filtros desejados. Todos são opcionais e podem ser combinados.</p>
		<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="fafar_cemed_report">
			<?php wp_nonce_field( 'fafar_cemed_report', '_wpnonce', false ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="cemed-report-from">De</label></th><td><input id="cemed-report-from" name="de" type="date"></td></tr>
				<tr><th scope="row"><label for="cemed-report-to">Até</label></th><td><input id="cemed-report-to" name="ate" type="date"></td></tr>
				<tr><th scope="row"><label for="cemed-report-year">Ano</label></th><td><input id="cemed-report-year" name="ano" type="number" min="1900" max="9999" step="1" placeholder="Ex.: 2026"><p class="description">Com as datas, considera somente a interseção dos períodos.</p></td></tr>
				<tr><th scope="row"><label for="cemed-report-topic">Tema</label></th><td><input class="regular-text" id="cemed-report-topic" name="tema" type="search"><p class="description">Busca o texto informado na pergunta e na resposta registrada.</p></td></tr>
			</table>
			<?php submit_button( 'Gerar relatório em PDF' ); ?>
		</form>
	</div>
	<?php
}

/** Produz um PDF textual paginado com as fontes padrão do formato. */
function fafar_cemed_report_pdf( $lines ) {
	$pages = array();
	$page = array();
	foreach ( $lines as $line ) {
		$encoded = iconv( 'UTF-8', 'Windows-1252//TRANSLIT', $line );
		if ( false === $encoded ) { $encoded = '?'; }
		foreach ( explode( "\n", wordwrap( $encoded, 90, "\n", true ) ) as $part ) {
			if ( count( $page ) >= 52 ) { $pages[] = $page; $page = array(); }
			$page[] = $part;
		}
	}
	if ( $page ) { $pages[] = $page; }
	$objects = array( 1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>' );
	$kids = array();
	foreach ( $pages as $index => $page_lines ) {
		$page_id = 4 + $index * 2; $content_id = $page_id + 1;
		$kids[] = $page_id . ' 0 R';
		$stream = "BT\n/F1 10 Tf\n45 800 Td\n14 TL\n";
		foreach ( $page_lines as $line ) {
			$line = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $line );
			$stream .= '(' . $line . ") Tj\nT*\n";
		}
		$stream .= "ET\n";
		$objects[ $page_id ] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents $content_id 0 R >>";
		$objects[ $content_id ] = '<< /Length ' . strlen( $stream ) . ">>\nstream\n" . $stream . 'endstream';
	}
	$objects[2] = '<< /Type /Pages /Count ' . count( $kids ) . ' /Kids [' . implode( ' ', $kids ) . '] >>';
	ksort( $objects );
	$pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets = array();
	foreach ( $objects as $id => $object ) { $offsets[$id] = strlen( $pdf ); $pdf .= "$id 0 obj\n$object\nendobj\n"; }
	$xref = strlen( $pdf ); $size = count( $objects ) + 1;
	$pdf .= "xref\n0 $size\n0000000000 65535 f \n";
	foreach ( $offsets as $offset ) { $pdf .= sprintf( "%010d 00000 n \n", $offset ); }
	return $pdf . "trailer\n<< /Size $size /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
}

function fafar_cemed_report_download() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Acesso negado.' ); }
	check_admin_referer( 'fafar_cemed_report' );
	$values = array();
	foreach ( array( 'de', 'ate', 'ano', 'tema' ) as $key ) {
		if ( isset( $_GET[$key] ) && ! is_scalar( $_GET[$key] ) ) { wp_die( 'Filtro inválido.' ); }
		$values[$key] = isset( $_GET[$key] ) ? trim( sanitize_text_field( wp_unslash( $_GET[$key] ) ) ) : '';
	}
	foreach ( array( 'de', 'ate' ) as $key ) {
		if ( '' === $values[$key] ) { continue; }
		$date = DateTime::createFromFormat( '!Y-m-d', $values[$key] );
		if ( ! $date || $date->format( 'Y-m-d' ) !== $values[$key] ) { wp_die( 'Informe datas válidas.' ); }
	}
	if ( $values['de'] && $values['ate'] && $values['de'] > $values['ate'] ) { wp_die( 'A data inicial deve ser anterior ou igual à data final.' ); }
	if ( $values['ano'] && ( ! preg_match( '/^\d{4}$/', $values['ano'] ) || (int) $values['ano'] < 1900 ) ) { wp_die( 'Informe um ano válido.' ); }
	$date_query = array();
	if ( $values['de'] || $values['ate'] ) {
		$range = array( 'inclusive' => true );
		if ( $values['de'] ) { $range['after'] = $values['de']; }
		if ( $values['ate'] ) { $range['before'] = $values['ate']; }
		$date_query[] = $range;
	}
	if ( $values['ano'] ) { $date_query[] = array( 'year' => (int) $values['ano'] ); }
	$args = array( 'post_type' => 'cemed_pergunta', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true, 'orderby' => 'none', 'date_query' => $date_query );
	if ( $values['tema'] ) {
		global $wpdb;
		$like = '%' . $wpdb->esc_like( $values['tema'] ) . '%';
		$filter = function ( $where ) use ( $wpdb, $like ) {
			return $where . $wpdb->prepare( " AND ({$wpdb->posts}.post_content LIKE %s OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} cm WHERE cm.post_id = {$wpdb->posts}.ID AND cm.meta_key IN ('_cemed_resposta', '_cemed_public_question') AND cm.meta_value LIKE %s))", $like, $like );
		};
		add_filter( 'posts_where', $filter );
	}
	$ids = get_posts( $args );
	if ( isset( $filter ) ) { remove_filter( 'posts_where', $filter ); }
	if ( $ids ) { update_meta_cache( 'post', $ids ); }
	$categories = array_fill_keys( fafar_cemed_answer_classification_options(), 0 ); $categories['Sem classificação'] = 0;
	$professions = array_fill_keys( array( 'Dentista', 'Farmacêutico', 'Médico', 'Enfermeiro', 'Outro', 'Não é profissional de saúde', 'Não informado' ), 0 );
	$states = array(); $known_states = fafar_ppgcf_brazilian_states();
	foreach ( $ids as $id ) {
		$selected = get_post_meta( $id, '_cemed_classificacao', true );
		$selected = is_array( $selected ) ? array_unique( array_filter( $selected, 'is_string' ) ) : array();
		$matched = false;
		foreach ( $selected as $item ) { if ( isset( $categories[$item] ) && 'Sem classificação' !== $item ) { ++$categories[$item]; $matched = true; } }
		if ( ! $matched ) { ++$categories['Sem classificação']; }
		$data = get_post_meta( $id, '_cemed_data', true ); $data = is_array( $data ) ? $data : array();
		$profession = $data['profissao_saude'] ?? '';
		if ( ( $data['profissional_saude'] ?? '' ) === 'Não' ) { $profession = 'Não é profissional de saúde'; }
		elseif ( ( $data['profissional_saude'] ?? '' ) !== 'Sim' || ! is_string( $profession ) || '' === $profession ) { $profession = 'Não informado'; }
		elseif ( ! isset( $professions[$profession] ) ) { $profession = 'Outro'; }
		++$professions[$profession];
		$uf = $data['uf'] ?? ''; $uf = is_string( $uf ) && isset( $known_states[$uf] ) ? $uf : 'Não informado';
		$states[$uf] = ( $states[$uf] ?? 0 ) + 1;
	}
	ksort( $states );
	$lines = array( 'Relatório de perguntas Cemed', 'Gerado em: ' . wp_date( 'd/m/Y H:i' ), 'Data de recebimento: ' . ( $values['de'] ? date_i18n( 'd/m/Y', strtotime( $values['de'] . ' 12:00:00' ) ) : 'sem início' ) . ' até ' . ( $values['ate'] ? date_i18n( 'd/m/Y', strtotime( $values['ate'] . ' 12:00:00' ) ) : 'sem fim' ), 'Ano: ' . ( $values['ano'] ?: 'todos' ), 'Tema: ' . ( $values['tema'] ?: 'todos' ), 'Total de perguntas: ' . count( $ids ), '', 'CLASSIFICAÇÕES DAS RESPOSTAS', 'Uma pergunta pode pertencer a mais de uma classificação.' );
	foreach ( $categories as $name => $count ) { $lines[] = $name . ': ' . $count; }
	$lines[] = ''; $lines[] = 'PROFISSÃO DO SOLICITANTE';
	foreach ( $professions as $name => $count ) { $lines[] = $name . ': ' . $count; }
	$lines[] = ''; $lines[] = 'ESTADO DO SOLICITANTE';
	if ( ! $states ) { $lines[] = 'Nenhuma pergunta encontrada.'; }
	foreach ( $states as $uf => $count ) { $lines[] = ( isset( $known_states[$uf] ) ? $uf . ' - ' . $known_states[$uf] : $uf ) . ': ' . $count; }
	$pdf = fafar_cemed_report_pdf( $lines );
	nocache_headers(); header( 'Content-Type: application/pdf' ); header( 'Content-Disposition: attachment; filename="relatorio-perguntas-cemed.pdf"' ); header( 'Content-Length: ' . strlen( $pdf ) );
	echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- PDF binário gerado no servidor.
	exit;
}
