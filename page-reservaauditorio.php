<?php
/** Formulário de reserva do auditório institucional. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$form_style_path = get_stylesheet_directory() . '/assets/css/inscricao.css';
$auditorium_style_path = get_stylesheet_directory() . '/assets/css/auditorium-reservation.css';
$form_script_path = get_stylesheet_directory() . '/assets/js/auditorium-reservation.js';
$today = current_time( 'Y-m-d' );
$last_date = ( new DateTimeImmutable( $today, wp_timezone() ) )->modify( '+365 days' )->format( 'Y-m-d' );
nocache_headers();
wp_enqueue_style( 'fafar-inscricao-form', get_stylesheet_directory_uri() . '/assets/css/inscricao.css', array( 'fafar-theme' ), file_exists( $form_style_path ) ? filemtime( $form_style_path ) : '1.0.0' );
wp_enqueue_style( 'fafar-auditorium-reservation', get_stylesheet_directory_uri() . '/assets/css/auditorium-reservation.css', array( 'fafar-inscricao-form' ), file_exists( $auditorium_style_path ) ? filemtime( $auditorium_style_path ) : '1.0.0' );
wp_enqueue_script( 'fafar-auditorium-reservation', get_stylesheet_directory_uri() . '/assets/js/auditorium-reservation.js', array(), file_exists( $form_script_path ) ? filemtime( $form_script_path ) : '1.0.0', true );
wp_localize_script( 'fafar-auditorium-reservation', 'fafarAuditoriumConfig', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'fafar_auditorium_form' ) ) );
get_header();
?>
<div id="primary" class="content-area">
	<?php while ( have_posts() ) : the_post(); ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<header class="entry-header"><?php the_title( '<h1 class="entry-title">', '</h1>' ); ?></header>
		<div class="entry-content clear fafar-inscricao-page-content">
			<?php do_action( 'fafar_entry_content_before' ); ?>
			<section aria-labelledby="fafar-auditorium-general-heading">
				<h2 id="fafar-auditorium-general-heading">Informações gerais</h2>
				<p>O envio deste formulário registra uma solicitação de reserva. A reserva só estará confirmada após o processamento interno do pedido e a confirmação do Setor de Apoio Logístico da Faculdade de Farmácia.</p>
			</section>
			<section aria-labelledby="fafar-auditorium-infrastructure-heading">
				<h2 id="fafar-auditorium-infrastructure-heading">Infraestrutura</h2>
				<p>O Auditório Aloísio Pimenta tem capacidade máxima para 192 pessoas e dispõe dos seguintes recursos:</p>
				<ul>
					<li>2 microfones sem fio;</li>
					<li>4 microfones com fio;</li>
					<li>2 microfones de mesa;</li>
					<li>1 projetor multimídia;</li>
					<li>1 notebook com configuração intermediária;</li>
					<li>1 caneta apresentadora de slides;</li>
					<li>1 aparelho de ar-condicionado.</li>
				</ul>
			</section>
			<p>Preencha os dados do evento e informe cada data e horário. É possível adicionar várias ocorrências, com no máximo 31 datas. Todas serão verificadas antes do envio.</p>
			<div id="fafar-auditorium-status" role="status" aria-live="polite" hidden></div>
			<form class="fafar-inscricao-form" id="fafar-auditorium-form" method="post" action="" novalidate>
				<fieldset class="fafar-form-section"><legend>Dados do solicitante e do evento</legend>
					<div class="fafar-form-grid">
						<div class="fafar-field fafar-field--full"><label for="applicant_name">* Nome do solicitante</label><input id="applicant_name" name="applicant_name" type="text" autocomplete="name" required></div>
						<div class="fafar-field"><label for="applicant_email">* E-mail do solicitante</label><input id="applicant_email" name="applicant_email" type="email" autocomplete="email" required></div>
						<div class="fafar-field"><label for="applicant_phone">* Telefone do solicitante</label><input id="applicant_phone" name="applicant_phone" type="tel" autocomplete="tel" required></div>
						<div class="fafar-field fafar-field--full"><label for="event_description">* Nome do evento</label><input id="event_description" name="description" type="text" required></div>
						<div class="fafar-field"><label for="expected_audience">* Previsão de público (máximo 192)</label><input id="expected_audience" name="expected_audience" type="number" min="0" max="192" step="1" required></div>
					</div>
				</fieldset>
				<fieldset class="fafar-form-section"><legend>Do uso dos equipamentos do auditório</legend>
					<?php
					$questions = array(
						'use_musical_instruments' => 'Utilizará instrumentos musicais?',
						'use_fafar_notebook' => 'Utilizará notebook da Faculdade de Farmácia?',
						'use_own_notebook' => 'Trará notebook próprio?',
						'use_internet_access' => 'Utilizará acesso à internet?',
					);
					foreach ( $questions as $key => $label ) : ?>
					<div class="fafar-field"><span class="fafar-label">* <?php echo esc_html( $label ); ?></span><div class="fafar-choice-list fafar-choice-list--inline"><label class="fafar-choice"><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="Sim" required> <span>Sim</span></label><label class="fafar-choice"><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="Não" required> <span>Não</span></label></div></div>
					<?php endforeach; ?>
				</fieldset>
				<fieldset class="fafar-form-section"><legend>Das datas do evento</legend>
					<div id="fafar-auditorium-occurrences" data-max-occurrences="31">
						<div class="fafar-auditorium-occurrence" data-occurrence>
							<div class="fafar-form-grid">
								<div class="fafar-field"><label>* Data<input data-date type="date" min="<?php echo esc_attr( $today ); ?>" max="<?php echo esc_attr( $last_date ); ?>" required></label></div>
								<div class="fafar-field"><label>* Hora início<input data-start type="time" value="18:00" required></label></div>
								<div class="fafar-field"><label>* Hora fim<input data-end type="time" value="22:00" required></label></div>
							</div>
							<p data-occurrence-status role="status" aria-live="polite"></p>
						</div>
					</div>
					<p><button type="button" id="fafar-auditorium-add">Adicionar data</button> <button type="button" id="fafar-auditorium-remove" hidden>Remover última data</button></p>
				</fieldset>
				<p class="fafar-form-actions"><button type="submit" class="fafar-submit">Enviar</button></p>
			</form>
			<?php do_action( 'fafar_entry_content_after' ); ?>
		</div>
	</article>
	<?php endwhile; ?>
</div>
<?php get_footer(); ?>
