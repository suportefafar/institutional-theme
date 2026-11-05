<?php
/** Regression checks for Bootstrap-compatible responsive table markup. */
define( 'ABSPATH', __DIR__ );
function add_filter() {}
require dirname( __DIR__ ) . '/inc/responsive-tables.php';

function check( $condition, $message ) {
	if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
}

$plain = '<p>Antes</p><table class="legacy" width="700"><tbody><tr><td>A</td></tr></tbody></table><p>Depois</p>';
$plain_out = fafar_responsive_tables( $plain );
check( str_contains( $plain_out, '<div class="table-responsive"><table class="legacy table" width="700">' ), 'Tabela editorial sem wrapper responsivo.' );
check( str_contains( $plain_out, '</table></div><p>Depois</p>' ), 'Wrapper editorial não foi fechado após a tabela.' );

$rich = '<table id="dados"><tbody><tr><td><a href="https://example.org/?a=1&amp;b=2"><img src="imagem.jpg" alt="Descrição preservada"></a></td><td>Texto &amp; dados</td></tr></tbody></table>';
$rich_out = fafar_responsive_tables( $rich );
foreach ( array( 'href="https://example.org/?a=1&amp;b=2"', 'src="imagem.jpg"', 'alt="Descrição preservada"', 'Texto &amp; dados' ) as $fragment ) {
	check( str_contains( $rich_out, $fragment ), "Conteúdo original perdido: {$fragment}" );
}
check( substr_count( $rich_out, '<td>' ) === substr_count( $rich, '<td>' ), 'A quantidade de células foi alterada.' );

$wrapped = '<div class="table-responsive"><table class="table"><tbody><tr><td>A</td></tr></tbody></table></div>';
check( fafar_responsive_tables( $wrapped ) === $wrapped, 'Wrapper ou classe existente foi duplicado.' );

$nested = '<table><tbody><tr><td><table class="inner"><tr><td>A</td></tr></table></td></tr></tbody></table>';
$nested_out = fafar_responsive_tables( $nested );
check( 1 === substr_count( $nested_out, 'class="table-responsive"' ), 'Tabela aninhada recebeu wrapper redundante.' );
check( 2 === preg_match_all( '/<table\b[^>]*\bclass=["\'][^"\']*\btable\b/iu', $nested_out ), 'Classe table não foi adicionada às tabelas aninhadas.' );

$without_table = '<p>Conteúdo sem tabela.</p>';
check( fafar_responsive_tables( $without_table ) === $without_table, 'Conteúdo sem tabela foi alterado.' );

echo "PASS: responsive markup preserves editorial content and handles existing and nested tables.\n";
