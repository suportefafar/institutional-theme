<?php
/** Relatórios agregados das perguntas Cemed. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', function () {
	add_submenu_page( 'cemed-perguntas', 'Relatório de perguntas', 'Relatórios', FAFAR_CEMED_MANAGE_CAPABILITY, 'cemed-relatorios', 'fafar_cemed_report_form' );
} );
add_action( 'admin_post_fafar_cemed_report', 'fafar_cemed_report_download' );

function fafar_cemed_report_form() {
	if ( ! current_user_can( FAFAR_CEMED_MANAGE_CAPABILITY ) ) { wp_die( 'Acesso negado.' ); }
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
			<p class="submit"><button type="submit" name="formato" value="pdf" class="button button-primary">Gerar relatório em PDF</button> <button type="submit" name="formato" value="csv" class="button">Baixar perguntas em CSV</button></p>
			<p>A planilha contém os dados individuais das perguntas selecionadas, sem estatísticas.</p>
		</form>
	</div>
	<?php
}

/** Reúne os dados da pergunta para os formatos de relatório. */
function fafar_cemed_report_question_data( $id ) {
	$post = get_post( $id );
	$row = array( 'ID' => $id, 'Data de recebimento' => $post->post_date, 'Pergunta' => $post->post_content );
	foreach ( get_post_meta( $id ) as $key => $values ) {
		if ( 0 !== strpos( $key, '_cemed_' ) || in_array( $key, array( '_cemed_feedback_token', '_cemed_legacy_history', '_cemed_external_token' ), true ) ) { continue; }
		$value = count( $values ) === 1 ? maybe_unserialize( $values[0] ) : array_map( 'maybe_unserialize', $values );
		if ( '_cemed_data' === $key && is_array( $value ) ) {
			foreach ( $value as $field => $field_value ) { $row[ 'Solicitação: ' . $field ] = $field_value; }
		} else { $row[ substr( $key, 7 ) ] = $value; }
	}
	return $row;
}

/** Rótulos legíveis compartilhados pelo PDF e pela planilha. */
function fafar_cemed_report_field_label( $field ) {
	$key = 0 === strpos( $field, 'Solicitação: ' ) ? substr( $field, strlen( 'Solicitação: ' ) ) : $field;
	$labels = array( 'email' => 'E-mail', 'nome' => 'Nome', 'telefone' => 'Telefone', 'uf' => 'UF', 'municipio' => 'Município', 'profissional_saude' => 'Profissional de saúde', 'profissao_saude' => 'Profissão', 'profissao_saude_outro' => 'Outra profissão', 'paciente_especifico' => 'Paciente específico', 'idade_paciente' => 'Idade do paciente', 'sexo_paciente' => 'Sexo do paciente', 'genero_paciente' => 'Gênero do paciente', 'doencas_preexistentes' => 'Doenças preexistentes', 'condicoes_especiais' => 'Condições especiais', 'informacoes_adicionais' => 'Informações adicionais', 'cpf' => 'CPF', 'consentimento' => 'Consentimento', 'consentimento_em' => 'Data do consentimento', 'code' => 'Código', 'legacy_code' => 'Código anterior', 'resposta' => 'Resposta', 'status' => 'Situação', 'classificacao' => 'Classificação' );
	return $labels[ $key ] ?? ucfirst( str_replace( '_', ' ', $key ) );
}

/** Exporta uma linha por pergunta, incluindo os dados e metadados registrados. */
function fafar_cemed_report_write_csv( $output, $ids ) {
	$rows = array();
	$columns = array( 'ID', 'Data de recebimento', 'Pergunta' );
	foreach ( $ids as $id ) {
		$row = fafar_cemed_report_question_data( $id );
		$columns = array_values( array_unique( array_merge( $columns, array_keys( $row ) ) ) );
		$rows[] = $row;
	}
	fwrite( $output, "\xEF\xBB\xBF" );
	fputcsv( $output, array_map( 'fafar_cemed_report_field_label', $columns ), ';', '"', '' );
	foreach ( $rows as $row ) {
		$cells = array();
		foreach ( $columns as $column ) {
			$value = $row[ $column ] ?? '';
			if ( is_array( $value ) || is_object( $value ) ) { $value = wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); }
			$value = (string) $value;
			if ( preg_match( '/^[\s\x00-\x1f]*[=+@\-\t\r]/u', $value ) ) { $value = "'" . $value; }
			$cells[] = $value;
		}
		fputcsv( $output, $cells, ';', '"', '' );
	}
}

function fafar_cemed_report_csv( $ids ) {
	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="perguntas-cemed.csv"' );
	$output = fopen( 'php://output', 'w' );
	fafar_cemed_report_write_csv( $output, $ids );
	fclose( $output );
	exit;
}

/** Formata listas e estruturas para leitura no PDF. */
function fafar_cemed_report_display_value( $value ) {
	if ( ! is_array( $value ) && ! is_object( $value ) ) { return (string) $value; }
	$items = array();
	foreach ( (array) $value as $key => $item ) {
		$text = fafar_cemed_report_display_value( $item );
		if ( '' !== $text ) { $items[] = is_string( $key ) ? ucfirst( str_replace( '_', ' ', $key ) ) . ': ' . $text : $text; }
	}
	return implode( '; ', $items );
}

/** Quebra texto pela largura das letras da fonte Helvetica (em pontos). */
function fafar_cemed_report_wrap( $text, $bold = false ) {
	$widths = array(278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584);
	$lines = array();
	foreach ( explode( "\n", $text ) as $paragraph ) {
		$line = ''; $width = 0; $last_space = false;
		for ( $i = 0, $length = strlen( $paragraph ); $i < $length; ++$i ) {
			$char = $paragraph[$i];
			$code = ord( $char );
			// Acentos usam a largura da letra base; símbolos recebem margem conservadora.
			if ( $code > 126 ) {
				$base = iconv( 'Windows-1252', 'ASCII//TRANSLIT', $char );
				$code = $base && strlen( $base ) === 1 ? ord( $base ) : 87;
			}
			$width += ( $widths[ $code - 32 ] ?? 944 ) * ( $bold ? 12 : 10 ) / 1000;
			$line .= $char;
			if ( ' ' === $char ) { $last_space = strlen( $line ) - 1; }
			if ( $width > 495 ) {
				$cut = false !== $last_space && $last_space > 0 ? $last_space : strlen( $line ) - 1;
				$lines[] = trim( substr( $line, 0, $cut ) );
				$i -= strlen( $line ) - $cut;
				$line = ''; $width = 0; $last_space = false;
			}
		}
		$lines[] = trim( $line );
	}
	return $lines;
}

/** Produz um PDF textual paginado com as fontes padrão do formato. */
function fafar_cemed_report_pdf( $lines ) {
	$pages = array();
	$page = array();
	foreach ( $lines as $line ) {
		if ( is_array( $line ) && isset( $line['break'] ) ) {
			if ( $page ) { $pages[] = $page; $page = array(); }
			continue;
		}
		$heading = is_array( $line );
		if ( $heading ) { $line = $line['heading']; }
		$encoded = iconv( 'UTF-8', 'Windows-1252//TRANSLIT', $line );
		if ( false === $encoded ) { $encoded = '?'; }
		if ( $heading && count( $page ) > 47 ) { $pages[] = $page; $page = array(); }
		foreach ( fafar_cemed_report_wrap( $encoded, $heading ) as $part ) {
			if ( count( $page ) >= 52 ) { $pages[] = $page; $page = array(); }
			$page[] = array( 'text' => $part, 'heading' => $heading );
		}
	}
	if ( $page ) { $pages[] = $page; }
	$objects = array( 1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>', 4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>' );
	$kids = array();
	foreach ( $pages as $index => $page_lines ) {
		$page_id = 5 + $index * 2; $content_id = $page_id + 1;
		$kids[] = $page_id . ' 0 R';
		$stream = "BT\n/F1 10 Tf\n45 800 Td\n14 TL\n";
		foreach ( $page_lines as $line ) {
			$text = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $line['text'] );
			$stream .= ( $line['heading'] ? '/F2 11 Tf' : '/F1 10 Tf' ) . "\n(" . $text . ") Tj\nT*\n";
		}
		$stream .= "ET\n0.5 G 45 48 m 550 48 l S\nBT /F1 8 Tf 0.4 g 45 32 Td (Cemed / UFMG) Tj 440 0 Td (" . ( $index + 1 ) . ' / ' . count( $pages ) . ") Tj ET\n";
		$objects[ $page_id ] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents $content_id 0 R >>";
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

/** Seção individual do PDF, sem campos vazios ou histórico legado. */
function fafar_cemed_report_detail_lines( $ids ) {
	$lines = array();
	$lines[] = array( 'break' => true );
	$lines[] = array( 'heading' => 'PERGUNTAS E INFORMAÇÕES COMPLETAS' );
	if ( ! $ids ) { $lines[] = 'Nenhuma pergunta encontrada.'; }

	foreach ( $ids as $index => $id ) {
		if ( $index > 0 ) { $lines[] = array( 'break' => true ); }
		$lines[] = '';
		$lines[] = array( 'heading' => 'Pergunta ' . ( $index + 1 ) );
		$lines[] = '____________________________________________________________';
		foreach ( fafar_cemed_report_question_data( $id ) as $field => $value ) {
			$value = fafar_cemed_report_display_value( $value );
			$value = preg_replace( '~<(?:br\s*/?|/(?:p|div|li|h[1-6]))>~i', "\n", (string) $value );
			$value = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( '' === trim( $value ) ) { continue; }
			$lines[] = array( 'heading' => fafar_cemed_report_field_label( $field ) );
			$lines[] = str_replace( array( "\r\n", "\r" ), "\n", $value );
			$lines[] = '';
		}
	}
	return $lines;
}

function fafar_cemed_report_download() {
	if ( ! current_user_can( FAFAR_CEMED_MANAGE_CAPABILITY ) ) { wp_die( 'Acesso negado.' ); }
	check_admin_referer( 'fafar_cemed_report' );
	$format = isset( $_GET['formato'] ) && is_string( $_GET['formato'] ) ? sanitize_key( wp_unslash( $_GET['formato'] ) ) : 'pdf';
	if ( ! in_array( $format, array( 'pdf', 'csv' ), true ) ) { wp_die( 'Formato inválido.' ); }
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
	$args = array( 'post_type' => 'cemed_pergunta', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true, 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), 'date_query' => $date_query, 'suppress_filters' => false );
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
	if ( 'csv' === $format ) { fafar_cemed_report_csv( $ids ); }
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
	$lines = array( array( 'heading' => 'Relatório de perguntas Cemed' ), 'Gerado em: ' . wp_date( 'd/m/Y H:i' ), 'Data de recebimento: ' . ( $values['de'] ? date_i18n( 'd/m/Y', strtotime( $values['de'] . ' 12:00:00' ) ) : 'sem início' ) . ' até ' . ( $values['ate'] ? date_i18n( 'd/m/Y', strtotime( $values['ate'] . ' 12:00:00' ) ) : 'sem fim' ), 'Ano: ' . ( $values['ano'] ?: 'todos' ), 'Tema: ' . ( $values['tema'] ?: 'todos' ), 'Total de perguntas: ' . count( $ids ), '', array( 'heading' => 'CLASSIFICAÇÕES DAS RESPOSTAS' ), 'Uma pergunta pode pertencer a mais de uma classificação.' );
	foreach ( $categories as $name => $count ) { $lines[] = $name . ': ' . $count; }
	$lines[] = ''; $lines[] = array( 'heading' => 'PROFISSÃO DO SOLICITANTE' );
	foreach ( $professions as $name => $count ) { $lines[] = $name . ': ' . $count; }
	$lines[] = ''; $lines[] = array( 'heading' => 'ESTADO DO SOLICITANTE' );
	if ( ! $states ) { $lines[] = 'Nenhuma pergunta encontrada.'; }
	foreach ( $states as $uf => $count ) { $lines[] = ( isset( $known_states[$uf] ) ? $uf . ' - ' . $known_states[$uf] : $uf ) . ': ' . $count; }
	$lines = array_merge( $lines, fafar_cemed_report_detail_lines( $ids ) );
	$pdf = fafar_cemed_report_pdf( $lines );
	nocache_headers(); header( 'Content-Type: application/pdf' ); header( 'Content-Disposition: attachment; filename="relatorio-perguntas-cemed.pdf"' ); header( 'Content-Length: ' . strlen( $pdf ) );
	echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- PDF binário gerado no servidor.
	exit;
}
