<?php
/** Verifica os dados exportados e gera uma amostra sintética para inspeção do PDF. */
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
function add_action( ...$args ) {}
function get_post( $id ) { return (object) array( 'post_date' => '2026-11-18 09:00:00', 'post_content' => 'Como armazenar o medicamento? (Exemplo sintético)' ); }
function get_post_meta( $id ) {
	return array(
		'_cemed_data' => array( serialize( array( 'nome' => 'Pessoa de exemplo', 'email' => 'exemplo@example.test', 'uf' => '', 'doencas_preexistentes' => array(), 'condicoes_especiais' => array( 'Nenhuma' ) ) ) ),
		'_cemed_code' => array( 'CEMEDUFMG-1234567890' ),
		'_cemed_resposta' => array( '<p>Primeiro parágrafo.</p><p>' . str_repeat( 'Informação de exemplo com acentuação e palavras longas. ', 90 ) . '</p><p>Último parágrafo.</p>' ),
		'_cemed_feedback' => array( serialize( array( 'nota' => 5, 'comentarios' => 'Atendimento claro.' ) ) ),
		'_cemed_feedback_token' => array( 'TOKEN-PRIVADO' ),
		'_cemed_legacy_history' => array( 'HISTORICO-EXCLUIDO' ),
	);
}
function maybe_unserialize( $value ) { $result = @unserialize( $value ); return false === $result ? $value : $result; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function check_report( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
require dirname( __DIR__ ) . '/inc/modules/cemed-report.php';

$row = fafar_cemed_report_question_data( 1 );
check_report( ! isset( $row['legacy_history'] ) && ! isset( $row['feedback_token'] ), 'Histórico e token não podem ser exportados.' );
check_report( isset( $row['Solicitação: nome'], $row['resposta'], $row['feedback'] ), 'Dados individuais devem ser preservados.' );
check_report( 'Nome' === fafar_cemed_report_field_label( 'Solicitação: nome' ), 'Cabeçalho deve usar um rótulo legível.' );
check_report( 'Nenhuma' === fafar_cemed_report_display_value( array( 'Nenhuma' ) ), 'Listas não devem ser exibidas como JSON.' );
$csv = fopen( 'php://temp', 'w+' );
fafar_cemed_report_write_csv( $csv, array( 1, 2 ) );
rewind( $csv );
$export = stream_get_contents( $csv );
check_report( false === strpos( $export, 'HISTORICO-EXCLUIDO' ) && false === strpos( $export, 'TOKEN-PRIVADO' ), 'CSV não deve conter histórico ou token.' );
check_report( false === strpos( $export, 'Solicitação:' ) && false !== strpos( $export, 'Nome' ), 'CSV deve usar os mesmos rótulos legíveis.' );
rewind( $csv );
$header = fgetcsv( $csv, 0, ';', '"', '' );
$first = fgetcsv( $csv, 0, ';', '"', '' );
$second = fgetcsv( $csv, 0, ';', '"', '' );
check_report( count( $header ) === count( $first ) && count( $first ) === count( $second ) && false === fgetcsv( $csv, 0, ';', '"', '' ), 'CSV deve conter exatamente uma linha por pergunta, com colunas alinhadas.' );
fclose( $csv );
$lines = fafar_cemed_report_detail_lines( array( 1, 2 ) );
$text = json_encode( $lines, JSON_UNESCAPED_UNICODE );
foreach ( array( 'TOKEN-PRIVADO', 'HISTORICO-EXCLUIDO', 'Solicitação:', 'Não informado', '[]' ) as $excluded ) {
	check_report( false === strpos( $text, $excluded ), 'Texto inesperado no PDF: ' . $excluded );
}
check_report( false !== strpos( $text, 'Último parágrafo.' ), 'Respostas longas devem permanecer completas.' );
$pdf = fafar_cemed_report_pdf( array_merge( array( array( 'heading' => 'Relatório de perguntas Cemed' ), 'AMOSTRA SINTÉTICA — SEM DADOS REAIS', 'Total de perguntas: 2' ), $lines ) );
check_report( 1 === preg_match( '~/Type /Pages /Count ([0-9]+)~', $pdf, $matches ) && (int) $matches[1] >= 3, 'PDF deve paginar o conteúdo longo.' );
check_report( false !== strpos( $pdf, '/Helvetica-Bold' ), 'PDF deve ter hierarquia tipográfica.' );
if ( isset( $argv[1] ) ) { file_put_contents( $argv[1], $pdf ); }
echo "PASS: dados completos, exclusões, rótulos e PDF paginado.\n";
