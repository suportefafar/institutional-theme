<?php
/** Avaliação da resposta enviada ao solicitante por link individual. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

nocache_headers();
header( 'Referrer-Policy: no-referrer' );
$token = isset( $_GET['codigo'] ) && is_scalar( $_GET['codigo'] ) ? sanitize_text_field( wp_unslash( $_GET['codigo'] ) ) : '';
$question = fafar_cemed_feedback_question( $token );
$result = $question ? fafar_cemed_handle_feedback( $question, $token ) : null;
$feedback = $question ? get_post_meta( $question->ID, '_cemed_feedback', true ) : null;
$feedback = is_array( $feedback ) ? $feedback : array();
$style = get_stylesheet_directory() . '/assets/css/cemed.css';
wp_enqueue_style( 'fafar-cemed', get_stylesheet_directory_uri() . '/assets/css/cemed.css', array( 'fafar-theme' ), file_exists( $style ) ? filemtime( $style ) : '1.0.0' );
get_header();
?>

<div id="primary" class="content-area">
	<?php while ( have_posts() ) : the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header"><?php the_title( '<h1 class="entry-title">', '</h1>' ); ?></header>
			<div class="entry-content clear fafar-cemed-page">
				<?php do_action( 'fafar_entry_content_before' ); ?>
				<h2>Avalie a resposta do Cemed</h2>
				<?php if ( ! $question ) : ?>
					<p class="fafar-cemed-message is-error" role="alert">O link de avaliação é inválido ou não está disponível.</p>
				<?php elseif ( $feedback ) : ?>
					<p class="fafar-cemed-message is-success" role="status">Obrigado! Sua avaliação da resposta <?php echo esc_html( fafar_cemed_question_code( $question->ID ) ); ?> foi registrada.</p>
				<?php else : ?>
					<p>Sua avaliação ajuda o Cemed a aprimorar o Serviço de Informação sobre Medicamentos.</p>
					<?php if ( is_wp_error( $result ) ) : ?><p class="fafar-cemed-message is-error" role="alert"><?php echo esc_html( $result->get_error_message() ); ?></p><?php endif; ?>
					<form class="fafar-cemed-form fafar-cemed-feedback" method="post" action="<?php echo esc_url( add_query_arg( 'codigo', $token, get_permalink() ) ); ?>">
						<?php wp_nonce_field( 'fafar_cemed_feedback_' . $question->ID, 'fafar_cemed_feedback_nonce' ); ?>
						<input type="hidden" name="fafar_cemed_feedback_action" value="submit">
						<div class="fafar-cemed-field"><label for="cemed-codigo-resposta">Código da resposta</label><input id="cemed-codigo-resposta" type="text" value="<?php echo esc_attr( fafar_cemed_question_code( $question->ID ) ); ?>" readonly></div>
						<fieldset>
							<legend>Sua avaliação</legend>
							<div class="fafar-cemed-field"><span class="fafar-cemed-label" id="cemed-duvida-label">A sua dúvida foi esclarecida? <span aria-hidden="true">*</span></span><div class="fafar-cemed-choices" role="group" aria-labelledby="cemed-duvida-label"><?php foreach ( array( 'Sim', 'Não', 'Parcialmente' ) as $option ) : ?><label><input type="radio" name="duvida_esclarecida" value="<?php echo esc_attr( $option ); ?>" <?php checked( fafar_cemed_post_value( 'duvida_esclarecida' ), $option ); ?> required> <?php echo esc_html( $option ); ?></label><?php endforeach; ?></div></div>
							<div class="fafar-cemed-field"><span class="fafar-cemed-label" id="cemed-linguagem-label">Os termos e a linguagem utilizados na resposta foram de fácil compreensão? <span aria-hidden="true">*</span></span><div class="fafar-cemed-choices" role="group" aria-labelledby="cemed-linguagem-label"><?php foreach ( array( 'Sim', 'Não', 'Parcialmente' ) as $option ) : ?><label><input type="radio" name="linguagem_clara" value="<?php echo esc_attr( $option ); ?>" <?php checked( fafar_cemed_post_value( 'linguagem_clara' ), $option ); ?> required> <?php echo esc_html( $option ); ?></label><?php endforeach; ?></div></div>
							<div class="fafar-cemed-field"><label for="cemed-nota">Qual nota você atribui para a qualidade da resposta apresentada à sua solicitação de informação sobre medicamentos? <span aria-hidden="true">*</span></label><p class="fafar-cemed-help">1 = muito ruim; 5 = muito bom.</p><select id="cemed-nota" name="nota" required><option value="">Selecione</option><?php foreach ( array( '1', '2', '3', '4', '5' ) as $note ) : ?><option value="<?php echo esc_attr( $note ); ?>" <?php selected( fafar_cemed_post_value( 'nota' ), $note ); ?>><?php echo esc_html( $note ); ?></option><?php endforeach; ?></select></div>
							<div class="fafar-cemed-field"><label for="cemed-comentarios">Se desejar, apresente seus comentários, críticas e sugestões</label><textarea id="cemed-comentarios" name="comentarios" rows="5" maxlength="5000"><?php echo esc_textarea( fafar_cemed_post_value( 'comentarios' ) ); ?></textarea></div>
						</fieldset>
						<button type="submit">Enviar avaliação</button>
					</form>
				<?php endif; ?>
			</div>
		</article>
	<?php endwhile; ?>
</div>

<?php get_footer(); ?>
