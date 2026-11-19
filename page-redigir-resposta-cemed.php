<?php
/** Formulário restrito à redação de uma resposta por token. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
nocache_headers();
header( 'Referrer-Policy: no-referrer' );
header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
header( 'X-Frame-Options: DENY' );
$token = isset( $_GET['codigo'] ) && is_string( $_GET['codigo'] ) ? wp_unslash( $_GET['codigo'] ) : '';
$question = fafar_cemed_external_answer_question( $token );
$result = null;
if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	$result = fafar_cemed_save_external_answer( $token );
	$question = fafar_cemed_external_answer_question( $token );
}
if ( ! $question ) { status_header( 404 ); }
$answer = $question ? get_post_meta( $question->ID, '_cemed_external_answer', true ) : '';
if ( $question && is_wp_error( $result ) ) { $answer = fafar_cemed_post_value( 'cemed_external_answer' ); }
wp_enqueue_style( 'fafar-cemed', get_stylesheet_directory_uri() . '/assets/css/cemed.css', array( 'fafar-theme' ), filemtime( get_stylesheet_directory() . '/assets/css/cemed.css' ) );
if ( $question ) { wp_enqueue_editor(); }
get_header();
?>
<main id="primary" class="content-area">
	<article class="fafar-cemed-page entry-content">
		<h1>Redigir resposta ao Cemed</h1>
		<?php if ( ! $question ) : ?>
			<p>Este link é inválido ou não está mais disponível.</p>
		<?php else : ?>
			<p>Escreva e salve sua resposta para revisão pela equipe do Cemed. Você pode atualizá-la enquanto este link estiver disponível.</p>
			<?php if ( true === $result ) : ?><p class="fafar-cemed-message is-success" role="status">Resposta salva com sucesso. A equipe do Cemed fará a revisão e o envio.</p><?php endif; ?>
			<?php if ( is_wp_error( $result ) ) : ?><p class="fafar-cemed-message is-error" role="alert"><?php echo esc_html( $result->get_error_message() ); ?></p><?php endif; ?>
			<form class="fafar-cemed-form" method="post" action="<?php echo esc_url( add_query_arg( 'codigo', $token, get_permalink() ) ); ?>">
				<?php wp_nonce_field( 'cemed_external_' . $token, 'cemed_external_nonce', false ); ?>
				<label for="cemed-external-answer">Resposta</label>
				<?php wp_editor( $answer, 'cemed-external-answer', array( 'textarea_name' => 'cemed_external_answer', 'textarea_rows' => 14, 'media_buttons' => false, 'quicktags' => false, 'tinymce' => array( 'toolbar1' => 'bold,italic,bullist,numlist,blockquote,link,unlink,undo,redo', 'toolbar2' => '', 'menubar' => false, 'paste_as_text' => true ) ) ); ?>
				<p><button type="submit">Salvar resposta</button></p>
			</form>
		<?php endif; ?>
	</article>
</main>
<?php get_footer(); ?>
