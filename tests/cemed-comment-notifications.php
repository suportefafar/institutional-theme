<?php
/** Regression checks for Cemed comment email recipients. */
define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$GLOBALS['registered_filters'] = array();
function add_action( ...$args ) {}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['registered_filters'][ $hook ] = array( $callback, $priority, $accepted_args );
}
function absint( $value ) { return abs( (int) $value ); }
function get_comment( $id ) { return $GLOBALS['comments'][ $id ] ?? null; }
function get_post( $id ) { return $GLOBALS['posts'][ $id ] ?? null; }
function get_post_type( $id ) { return get_post( $id )->post_type ?? null; }
function get_post_meta( $id, $key, $single ) { return $GLOBALS['meta'][ $id ][ $key ] ?? ''; }
function wp_get_environment_type() { return $GLOBALS['environment']; }

function check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}

$GLOBALS['posts'] = array(
	10 => (object) array( 'ID' => 10, 'post_type' => 'cemed_pergunta', 'post_status' => 'publish' ),
	20 => (object) array( 'ID' => 20, 'post_type' => 'post', 'post_status' => 'publish' ),
	21 => (object) array( 'ID' => 21, 'post_type' => 'post', 'post_status' => 'publish' ),
);
$GLOBALS['meta'] = array( 20 => array( '_cemed_question_id' => 10 ) );
$GLOBALS['comments'] = array(
	100 => (object) array( 'comment_ID' => 100, 'comment_post_ID' => 20, 'comment_type' => 'comment', 'comment_approved' => '1' ),
	101 => (object) array( 'comment_ID' => 101, 'comment_post_ID' => 20, 'comment_type' => '', 'comment_approved' => '0' ),
	102 => (object) array( 'comment_ID' => 102, 'comment_post_ID' => 21, 'comment_type' => 'comment', 'comment_approved' => '1' ),
	103 => (object) array( 'comment_ID' => 103, 'comment_post_ID' => 20, 'comment_type' => 'pingback', 'comment_approved' => '1' ),
);
$GLOBALS['environment'] = 'production';

require dirname( __DIR__ ) . '/inc/modules/cemed.php';

foreach ( array( 'notify_post_author', 'notify_moderator', 'comment_notification_recipients', 'comment_moderation_recipients' ) as $hook ) {
	check( isset( $GLOBALS['registered_filters'][ $hook ] ), "Filter {$hook} was not registered." );
	check( 2 === $GLOBALS['registered_filters'][ $hook ][2], "Filter {$hook} does not receive the comment ID." );
}

$recipients = fafar_cemed_comment_notification_recipients( array( 'author@example.test' ), 100 );
check( array( 'author@example.test', 'sim.cemedufmg@gmail.com' ) === $recipients, 'Cemed recipient was not appended.' );
$recipients = fafar_cemed_comment_notification_recipients( array( 'SIM.CEMEDUFMG@GMAIL.COM' ), 100 );
check( 1 === count( $recipients ), 'Cemed recipient was duplicated.' );
check( array( 'author@example.test' ) === fafar_cemed_comment_notification_recipients( array( 'author@example.test' ), 102 ), 'Non-Cemed post received the Cemed recipient.' );
check( array( 'author@example.test' ) === fafar_cemed_comment_notification_recipients( array( 'author@example.test' ), 103 ), 'Pingback received the Cemed recipient.' );

check( true === fafar_cemed_enable_comment_notification( false, 100 ), 'Published Cemed comment notification was not enabled.' );
check( false === fafar_cemed_enable_comment_notification( false, 102 ), 'Non-Cemed notification setting was changed.' );
check( true === fafar_cemed_enable_moderation_notification( false, 101 ), 'Pending Cemed moderation notification was not enabled.' );
check( false === fafar_cemed_enable_moderation_notification( false, 100 ), 'Approved comment incorrectly triggered moderation notification.' );

$GLOBALS['environment'] = 'development';
check( array( 'author@example.test' ) === fafar_cemed_comment_notification_recipients( array( 'author@example.test' ), 100 ), 'Real recipient was enabled outside production.' );
check( false === fafar_cemed_enable_comment_notification( false, 100 ), 'Comment email was forced outside production.' );

echo "PASS: Cemed comment notifications append the requested recipient for published and pending comments only in production.\n";
