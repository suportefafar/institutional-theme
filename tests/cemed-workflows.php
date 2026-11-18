<?php
/** Verifica a retirada das páginas, o submenu e o link das notificações. */
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'OBJECT', 'OBJECT' );
function add_action( $hook, $callback, ...$args ) { $GLOBALS['actions'][$hook][] = $callback; }
function add_filter( ...$args ) {}
function get_option( $key ) { return $GLOBALS['options'][$key] ?? false; }
function update_option( $key, $value, ...$args ) { $GLOBALS['options'][$key] = $value; }
function get_page_by_path( $slug, ...$args ) { return $GLOBALS['pages'][$slug] ?? null; }
function wp_trash_post( $id ) {
	$GLOBALS['trashed'][] = $id;
	foreach ( $GLOBALS['pages'] as $slug => $page ) { if ( $page->ID === $id ) { unset( $GLOBALS['pages'][$slug] ); } }
	return true;
}
function add_menu_page( ...$args ) {}
function add_submenu_page( ...$args ) { $GLOBALS['submenus'][] = $args; }
function get_post( $id ) { return (object) array( 'post_type' => 'cemed_pergunta' ); }
function get_post_meta( $id, $key, $single ) { return '_cemed_code' === $key ? 'CEMEDUFMG-1234567890' : array( 'nome' => 'Exemplo', 'email' => 'exemplo@example.test' ); }
function is_email( $email ) { return filter_var( $email, FILTER_VALIDATE_EMAIL ); }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function esc_url( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function admin_url( $path ) { return $GLOBALS['admin_base'] . $path; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function check_workflow( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
require dirname( __DIR__ ) . '/inc/modules/cemed.php';

$GLOBALS['pages'] = array( 'perguntas-cemed' => (object) array( 'ID' => 10 ), 'gerenciar-perguntas-cemed' => (object) array( 'ID' => 20 ) );
$GLOBALS['trashed'] = array();
fafar_cemed_retire_management_pages();
check_workflow( array( 10, 20 ) === $GLOBALS['trashed'] && get_option( 'fafar_cemed_management_pages_retired' ), 'A migração deve retirar apenas as duas páginas antigas.' );
fafar_cemed_retire_management_pages();
check_workflow( count( $GLOBALS['trashed'] ) === 2, 'A migração deve executar uma vez.' );
foreach ( $GLOBALS['actions']['admin_menu'] as $callback ) { $callback(); }
check_workflow( 'Painel' === $GLOBALS['submenus'][0][2] && 'cemed-perguntas' === $GLOBALS['submenus'][0][4], 'O primeiro submenu deve ser Painel.' );

$previous = getenv( 'CEMED_NOTIFICATION_EMAILS' );
putenv( 'CEMED_NOTIFICATION_EMAILS=equipe@example.test' );
foreach ( array( 'https://www.farmacia.ufmg.br/wp-admin/', 'https://farmacia.ufmg.br/wp-admin/', 'http://www.farmacia.ufmg.br/wp-admin/' ) as $base ) {
	$GLOBALS['admin_base'] = $base;
	$message = fafar_cemed_build_notification( 1, 'team' );
	check_workflow( false !== strpos( $message['body'], 'https://farmacia.ufmg.br/wp-admin/admin.php?page=cemed-perguntas' ), 'A notificação deve usar o endereço de produção solicitado.' );
	check_workflow( false === strpos( $message['body'], '/gerenciar-perguntas-cemed/' ), 'A notificação não deve apontar para a página retirada.' );
}
$GLOBALS['admin_base'] = 'http://farmacia.local/wp-admin/';
$message = fafar_cemed_build_notification( 1, 'team' );
check_workflow( false !== strpos( $message['body'], 'http://farmacia.local/wp-admin/admin.php?page=cemed-perguntas' ), 'Dev deve usar seu próprio painel.' );
putenv( false === $previous ? 'CEMED_NOTIFICATION_EMAILS' : 'CEMED_NOTIFICATION_EMAILS=' . $previous );
echo "PASS: migração das páginas, submenu Painel e links de notificação.\n";
