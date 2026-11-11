<?php
/** Consulta autenticada das perguntas recebidas pelo Cemed. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! is_user_logged_in() ) {
	auth_redirect();
	exit;
}

nocache_headers();
$manage_result = ! empty( $fafar_cemed_admin ) ? ( $GLOBALS['fafar_cemed_admin_result'] ?? null ) : fafar_cemed_delete_public_post();
if ( null === $manage_result && empty( $fafar_cemed_admin ) ) {
	$manage_result = fafar_cemed_manage_question();
}
$page_number = max( 1, get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : ( isset( $_GET['pagina'] ) ? absint( $_GET['pagina'] ) : 1 ) );
$updated_id  = isset( $_GET['atualizada'] ) && is_scalar( $_GET['atualizada'] ) ? absint( $_GET['atualizada'] ) : 0;
$deleted_id  = isset( $_GET['excluida'] ) && is_scalar( $_GET['excluida'] ) ? absint( $_GET['excluida'] ) : 0;
$confirmation = isset( $_GET['confirmacao'] ) && is_scalar( $_GET['confirmacao'] ) ? sanitize_text_field( wp_unslash( $_GET['confirmacao'] ) ) : '';
$show_update = $updated_id && $confirmation && wp_verify_nonce( $confirmation, 'fafar_cemed_updated_' . $updated_id );
$show_delete = $deleted_id && $confirmation && wp_verify_nonce( $confirmation, 'fafar_cemed_deleted_' . $deleted_id );
$detail_id = ! empty( $fafar_cemed_admin ) && isset( $_GET['pergunta'] ) && is_scalar( $_GET['pergunta'] ) ? absint( $_GET['pergunta'] ) : 0;
$search = ! empty( $fafar_cemed_admin ) && isset( $_GET['busca'] ) && is_scalar( $_GET['busca'] ) ? sanitize_text_field( wp_unslash( $_GET['busca'] ) ) : '';
$dates = array();
foreach ( array( 'de' => 'after', 'ate' => 'before' ) as $key => $bound ) {
	$value = isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
	$date = DateTime::createFromFormat( '!Y-m-d', $value );
	if ( ! empty( $fafar_cemed_admin ) && $date && $date->format( 'Y-m-d' ) === $value ) { $dates[ $bound ] = $value; }
}
$args = array( 'post_type' => 'cemed_pergunta', 'post_status' => 'publish', 'posts_per_page' => ! empty( $fafar_cemed_admin ) ? 10 : 20, 'paged' => $page_number, 'orderby' => 'date', 'order' => 'DESC' );
if ( $detail_id ) { $args['p'] = $detail_id; $args['paged'] = 1; }
if ( $dates && ! $detail_id ) { $args['date_query'] = array( array_merge( $dates, array( 'inclusive' => true ) ) ); }
if ( $search && ! $detail_id ) {
	global $wpdb;
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$search_filter = function ( $where ) use ( $wpdb, $like ) {
		return $where . $wpdb->prepare( " AND ({$wpdb->posts}.post_content LIKE %s OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} cm WHERE cm.post_id = {$wpdb->posts}.ID AND cm.meta_key IN ('_cemed_resposta', '_cemed_public_question') AND cm.meta_value LIKE %s))", $like, $like );
	};
	add_filter( 'posts_where', $search_filter );
}
$questions = new WP_Query( $args );
if ( isset( $search_filter ) ) { remove_filter( 'posts_where', $search_filter ); }
$question_ids = get_posts( array( 'post_type' => 'cemed_pergunta', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true, 'orderby' => 'none' ) );
$feedback_options = array( 'Sim', 'Não', 'Parcialmente' );
$cemed_stats = array(
	'total' => count( $question_ids ),
	'answered' => 0,
	'evaluated' => 0,
	'rating_sum' => 0,
	'ratings' => array_fill_keys( array( '1', '2', '3', '4', '5' ), 0 ),
	'clarified' => array_fill_keys( $feedback_options, 0 ),
	'language' => array_fill_keys( $feedback_options, 0 ),
);
foreach ( $question_ids as $cemed_question_id ) {
	if ( 'respondida' === get_post_meta( $cemed_question_id, '_cemed_status', true ) ) {
		++$cemed_stats['answered'];
	}
	$stored_feedback = get_post_meta( $cemed_question_id, '_cemed_feedback', true );
	if ( ! is_array( $stored_feedback ) ) {
		continue;
	}
	foreach ( array( 'duvida_esclarecida' => 'clarified', 'linguagem_clara' => 'language' ) as $feedback_field => $stat_field ) {
		$choice = $stored_feedback[ $feedback_field ] ?? '';
		if ( is_string( $choice ) && isset( $cemed_stats[ $stat_field ][ $choice ] ) ) {
			++$cemed_stats[ $stat_field ][ $choice ];
		}
	}
	$rating = isset( $stored_feedback['nota'] ) ? (string) $stored_feedback['nota'] : '';
	if ( isset( $cemed_stats['ratings'][ $rating ] ) ) {
		++$cemed_stats['ratings'][ $rating ];
		++$cemed_stats['evaluated'];
		$cemed_stats['rating_sum'] += (int) $rating;
	}
}
$status_labels = array( 'nova' => 'Nova', 'em_analise' => 'Em análise', 'respondida' => 'Respondida' );
$email_labels = array( 'sent' => 'Enviado', 'failed' => 'Falha no envio', 'suppressed_non_production' => 'Não enviado no dev' );
$style       = get_stylesheet_directory() . '/assets/css/cemed.css';
$script      = get_stylesheet_directory() . '/assets/js/cemed-management.js';
wp_enqueue_style( 'fafar-cemed', get_stylesheet_directory_uri() . '/assets/css/cemed.css', array( 'fafar-theme' ), file_exists( $style ) ? filemtime( $style ) : '1.0.0' );
wp_enqueue_script( 'fafar-cemed-management', get_stylesheet_directory_uri() . '/assets/js/cemed-management.js', array(), file_exists( $script ) ? filemtime( $script ) : '1.0.0', true );
wp_enqueue_editor();
if ( empty( $fafar_cemed_admin ) ) { get_header(); }
?>

<div id="primary" class="content-area">
	<?php if ( ! empty( $fafar_cemed_admin ) ) : ?><div class="wrap"><h1>Perguntas Cemed</h1><?php else : ?><?php while ( have_posts() ) : the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header"><?php the_title( '<h1 class="entry-title">', '</h1>' ); ?></header>
			<?php endwhile; endif; ?>
			<div class="entry-content clear fafar-cemed-page fafar-cemed-list">
				<?php if ( ! empty( $fafar_cemed_admin ) && ! $detail_id ) : ?>

				<?php elseif ( ! empty( $fafar_cemed_admin ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( remove_query_arg( array( 'pergunta', 'atualizada', 'excluida', 'confirmacao' ), fafar_cemed_management_url() ) ); ?>">← Voltar à lista</a></p>
				<?php else : do_action( 'fafar_entry_content_before' ); endif; ?>
				<p>Área restrita a usuários autenticados. Revise o texto da pergunta antes de publicar a resposta para retirar dados pessoais ou informações clínicas identificáveis.</p>
				<?php if ( ! $detail_id ) : ?>
				<section class="fafar-cemed-stats" aria-labelledby="fafar-cemed-stats-title">
					<h2 id="fafar-cemed-stats-title">Estatísticas</h2>
					<div class="fafar-cemed-stat-grid">
						<div class="fafar-cemed-stat"><span>Perguntas recebidas</span><strong><?php echo esc_html( number_format_i18n( $cemed_stats['total'] ) ); ?></strong></div>
						<div class="fafar-cemed-stat"><span>Perguntas respondidas</span><strong><?php echo esc_html( number_format_i18n( $cemed_stats['answered'] ) ); ?></strong></div>
						<div class="fafar-cemed-stat"><span>Perguntas avaliadas</span><strong><?php echo esc_html( number_format_i18n( $cemed_stats['evaluated'] ) ); ?></strong></div>
						<div class="fafar-cemed-stat"><span>Média das notas</span><strong><?php echo $cemed_stats['evaluated'] ? esc_html( number_format_i18n( $cemed_stats['rating_sum'] / $cemed_stats['evaluated'], 2 ) . ' / 5' ) : '—'; ?></strong></div>
					</div>
					<h3>Avaliações por nota</h3>
					<dl class="fafar-cemed-rating-list">
						<?php foreach ( $cemed_stats['ratings'] as $rating_value => $rating_count ) : ?>
							<dt><?php echo esc_html( $rating_value ); ?> / 5</dt><dd><?php echo esc_html( number_format_i18n( $rating_count ) ); ?> pergunta(s)</dd>
						<?php endforeach; ?>
					</dl>
					<div class="fafar-cemed-feedback-breakdowns">
						<section>
							<h3>Dúvida esclarecida</h3>
							<dl class="fafar-cemed-feedback-list">
								<?php foreach ( $cemed_stats['clarified'] as $choice => $choice_count ) : ?>
									<dt><?php echo esc_html( $choice ); ?></dt><dd><?php echo esc_html( number_format_i18n( $choice_count ) ); ?> pessoa(s)</dd>
								<?php endforeach; ?>
							</dl>
						</section>
						<section>
							<h3>Linguagem clara</h3>
							<dl class="fafar-cemed-feedback-list">
								<?php foreach ( $cemed_stats['language'] as $choice => $choice_count ) : ?>
									<dt><?php echo esc_html( $choice ); ?></dt><dd><?php echo esc_html( number_format_i18n( $choice_count ) ); ?> pessoa(s)</dd>
								<?php endforeach; ?>
							</dl>
						</section>
					</div>
				</section>
				<?php endif; ?>
				<?php if ( ! empty( $fafar_cemed_admin ) && ! $detail_id ) : ?>
				<form method="get"><input type="hidden" name="page" value="cemed-perguntas">
					<label for="cemed-busca">Pesquisar na pergunta ou resposta</label> <input id="cemed-busca" name="busca" type="search" value="<?php echo esc_attr( $search ); ?>">
					<label for="cemed-de">De</label> <input id="cemed-de" name="de" type="date" value="<?php echo esc_attr( $dates['after'] ?? '' ); ?>">
					<label for="cemed-ate">Até</label> <input id="cemed-ate" name="ate" type="date" value="<?php echo esc_attr( $dates['before'] ?? '' ); ?>">
					<button class="button button-primary">Filtrar</button> <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=cemed-perguntas' ) ); ?>">Limpar</a>
				</form>
				<?php endif; ?>
				<?php if ( $show_update ) : ?>
					<p class="fafar-cemed-message is-success" role="status">Pergunta <?php echo esc_html( fafar_cemed_question_code( $updated_id ) ); ?> atualizada.</p>
				<?php endif; ?>
				<?php if ( $show_delete ) : ?>
					<p class="fafar-cemed-message is-success" role="status">Post da pergunta <?php echo esc_html( fafar_cemed_question_code( $deleted_id ) ); ?> excluído e enviado para a lixeira.</p>
				<?php endif; ?>
				<?php if ( is_wp_error( $manage_result ) ) : ?>
					<p class="fafar-cemed-message is-error" role="alert"><?php echo esc_html( $manage_result->get_error_message() ); ?></p>
				<?php endif; ?>
				<?php if ( ! $detail_id ) : ?><p><strong><?php echo esc_html( $questions->found_posts ); ?></strong> pergunta(s) recebida(s).</p><?php endif; ?>
				<?php if ( ! empty( $fafar_cemed_admin ) && ! $detail_id ) : ?>
					<?php if ( $questions->have_posts() ) : ?>
					<table class="widefat striped"><thead><tr><th>Código</th><th>Solicitante</th><th>Status</th><th>Data</th><th>Município</th><th><span class="screen-reader-text">Ação</span></th></tr></thead><tbody>
					<?php while ( $questions->have_posts() ) : $questions->the_post();
						$id = get_the_ID();
						$row_data = get_post_meta( $id, '_cemed_data', true );
						$row_data = is_array( $row_data ) ? $row_data : array();
						$row_status = get_post_meta( $id, '_cemed_status', true );
						$detail_url = add_query_arg( array( 'pergunta' => $id, 'pagina' => $page_number ), fafar_cemed_management_url() );
					?>
					<tr><td><?php echo esc_html( fafar_cemed_question_code( $id ) ); ?></td><td><?php echo esc_html( $row_data['nome'] ?? 'Sem nome' ); ?></td><td><?php echo esc_html( $status_labels[ $row_status ] ?? 'Nova' ); ?></td><td><?php echo esc_html( get_the_date( 'd/m/Y H:i' ) ); ?></td><td><?php echo esc_html( $row_data['municipio'] ?? '—' ); ?><?php echo ! empty( $row_data['uf'] ) ? esc_html( ' / ' . $row_data['uf'] ) : ''; ?></td><td><a class="button" href="<?php echo esc_url( $detail_url ); ?>" aria-label="<?php echo esc_attr( 'Ver pergunta ' . fafar_cemed_question_code( $id ) ); ?>">Ver</a></td></tr>
					<?php endwhile; wp_reset_postdata(); ?></tbody></table>
					<div class="tablenav bottom"><div class="tablenav-pages">
						<span class="displaying-num"><?php echo esc_html( sprintf( '%s pergunta(s)', number_format_i18n( $questions->found_posts ) ) ); ?></span>
						<nav class="fafar-cemed-pagination" aria-label="Páginas de perguntas">
							<?php if ( $questions->max_num_pages > 1 ) : ?>
								<?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'pagina', '%#%', fafar_cemed_management_url() ), 'format' => '', 'current' => $page_number, 'total' => $questions->max_num_pages, 'prev_text' => '← Anterior', 'next_text' => 'Próxima →' ) ) ); ?>
							<?php else : ?>
								<span aria-current="page">Página 1 de 1</span>
							<?php endif; ?>
						</nav>
					</div></div>
					<?php else : ?><p>Nenhuma pergunta encontrada.</p><?php endif; ?>
				<?php else : ?>
				<?php if ( $questions->have_posts() ) : ?>
					<?php while ( $questions->have_posts() ) : $questions->the_post(); ?>
						<?php
					$question_id = get_the_ID();
					$question_code = fafar_cemed_question_code( $question_id );
					$data = get_post_meta( $question_id, '_cemed_data', true );
					$data = is_array( $data ) ? $data : array();
					$status = get_post_meta( $question_id, '_cemed_status', true );
					$status = isset( $status_labels[ $status ] ) ? $status : 'nova';
					$answer = get_post_meta( $question_id, '_cemed_resposta', true );
					$classifications = get_post_meta( $question_id, '_cemed_classificacao', true );
					$classifications = is_array( $classifications ) ? $classifications : array();
					$classifications_other = get_post_meta( $question_id, '_cemed_classificacao_outro', true );
					$additional_needed = get_post_meta( $question_id, '_cemed_informacao_adicional', true );
					$additional_types = get_post_meta( $question_id, '_cemed_informacao_adicional_tipo', true );
					$additional_types = is_array( $additional_types ) ? $additional_types : array();
					$additional_types_other = get_post_meta( $question_id, '_cemed_informacao_adicional_outro', true );
					$additional_details = get_post_meta( $question_id, '_cemed_informacoes_adicionais_detalhes', true );
						$public_question = get_post_meta( $question_id, '_cemed_public_question', true );
						$public_question = is_string( $public_question ) && '' !== $public_question ? $public_question : get_the_content( null, false );
						$public_post_id = absint( get_post_meta( $question_id, '_cemed_public_post_id', true ) );
						$public_title = get_post_meta( $question_id, '_cemed_public_title', true );
						$public_title = is_string( $public_title ) ? $public_title : '';
						if ( $public_title && preg_match( '/^Pergunta ao Cemed\s+—\s+CEMEDUFMG-\d{10,}$/u', $public_title ) ) {
							$public_title = '';
						}
						if ( '' === $public_title && $public_post_id ) {
							$existing_public_title = get_the_title( $public_post_id );
							if ( is_string( $existing_public_title ) && ! preg_match( '/^Pergunta ao Cemed\s+—\s+CEMEDUFMG-\d{10,}$/u', $existing_public_title ) ) {
								$public_title = $existing_public_title;
							}
						}
						$feedback = get_post_meta( $question_id, '_cemed_feedback', true );
						$feedback = is_array( $feedback ) ? $feedback : array();
						$requester_email = get_post_meta( $question_id, '_cemed_email_requester', true );
						$team_email = get_post_meta( $question_id, '_cemed_email_team', true );
						$response_email = get_post_meta( $question_id, '_cemed_email_response', true );
						$direct_response_email = get_post_meta( $question_id, '_cemed_email_direct_response', true );
						$requester_email = is_array( $requester_email ) ? $requester_email : array();
						$team_email = is_array( $team_email ) ? $team_email : array();
						$response_email = is_array( $response_email ) ? $response_email : array();
						$direct_response_email = is_array( $direct_response_email ) ? $direct_response_email : array();
					?>
						<details class="fafar-cemed-item" id="cemed-pergunta-<?php echo esc_attr( $question_id ); ?>" <?php echo $detail_id || ( $show_update && $updated_id === $question_id ) || ( $show_delete && $deleted_id === $question_id ) ? 'open' : ''; ?>>
							<summary><strong><?php echo esc_html( $question_code ); ?> — <?php echo esc_html( $data['nome'] ?? 'Sem nome' ); ?></strong><span class="fafar-cemed-status is-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status_labels[ $status ] ); ?></span><span><?php echo esc_html( get_the_date( 'd/m/Y H:i' ) ); ?></span><span><?php echo esc_html( $data['municipio'] ?? '' ); ?><?php echo ! empty( $data['uf'] ) ? esc_html( ' / ' . $data['uf'] ) : ''; ?></span></summary>
							<div class="fafar-cemed-item-body">
								<?php if ( $public_post_id ) : ?>
									<p class="fafar-cemed-published"><strong>Resposta publicada:</strong> <a href="<?php echo esc_url( get_permalink( $public_post_id ) ); ?>" target="_blank" rel="noopener noreferrer">Abrir o post com a pergunta e a resposta ↗</a></p>
									<div class="fafar-cemed-post-actions" aria-label="Ações do post publicado">
										<?php $edit_post_url = get_edit_post_link( $public_post_id, '' ); ?>
										<?php if ( $edit_post_url ) : ?><a class="<?php echo ! empty( $fafar_cemed_admin ) ? 'button' : 'ffds-btn ffds-btn-secondary'; ?>" href="<?php echo esc_url( $edit_post_url ); ?>">Editar post</a><?php endif; ?>
										<?php if ( current_user_can( 'delete_post', $public_post_id ) ) : ?>
											<form method="post" action="<?php echo esc_url( fafar_cemed_management_url() ); ?>" data-cemed-delete-post data-confirm="Excluir este post? Ele será enviado para a lixeira e poderá ser recuperado pelo painel administrativo.">
												<?php wp_nonce_field( 'fafar_cemed_delete_public_post_' . $question_id . '_' . $public_post_id, 'fafar_cemed_delete_nonce' ); ?>
												<input type="hidden" name="fafar_cemed_manage_action" value="delete_public_post">
												<input type="hidden" name="cemed_question_id" value="<?php echo esc_attr( $question_id ); ?>">
												<input type="hidden" name="cemed_public_post_id" value="<?php echo esc_attr( $public_post_id ); ?>">
												<input type="hidden" name="cemed_page_number" value="<?php echo esc_attr( $page_number ); ?>">
												<button class="<?php echo ! empty( $fafar_cemed_admin ) ? 'button button-link-delete' : 'ffds-btn ffds-btn-danger'; ?>" type="submit">Excluir post</button>
											</form>
										<?php endif; ?>
									</div>
								<?php else : ?>
									<p class="fafar-cemed-pending"><strong>Post ainda não criado.</strong> Esta pergunta está aguardando resposta. <a href="#cemed-responder-<?php echo esc_attr( $question_id ); ?>">Ir para a resposta e publicação ↓</a></p>
								<?php endif; ?>
								<dl>
									<dt>Nome</dt><dd><?php echo esc_html( $data['nome'] ?? '—' ); ?></dd>
									<dt>E-mail</dt><dd><?php if ( ! empty( $data['email'] ) && is_email( $data['email'] ) ) : ?><a href="mailto:<?php echo esc_attr( $data['email'] ); ?>"><?php echo esc_html( $data['email'] ); ?></a><?php else : ?>—<?php endif; ?></dd>
									<dt>CPF</dt><dd><?php echo esc_html( $data['cpf'] ?? '—' ); ?></dd>
									<dt>Telefone</dt><dd><?php echo esc_html( $data['telefone'] ?? '—' ); ?></dd>
									<dt>UF</dt><dd><?php echo esc_html( $data['uf'] ?? '—' ); ?></dd>
									<dt>Município</dt><dd><?php echo esc_html( $data['municipio'] ?? '—' ); ?></dd>
									<dt>Profissional da saúde?</dt><dd><?php echo esc_html( $data['profissional_saude'] ?? '—' ); ?></dd>
									<?php if ( 'Sim' === ( $data['profissional_saude'] ?? '' ) ) : ?><dt>Profissão</dt><dd><?php echo esc_html( 'Outro' === ( $data['profissao_saude'] ?? '' ) ? ( $data['profissao_saude_outro'] ?? 'Outro' ) : ( $data['profissao_saude'] ?? '—' ) ); ?></dd><?php endif; ?>
									<dt>Envolve paciente específico?</dt><dd><?php echo esc_html( $data['paciente_especifico'] ?? '—' ); ?></dd>
									<?php if ( 'Sim' === ( $data['paciente_especifico'] ?? '' ) ) : ?>
										<dt class="is-patient-detail">Idade do paciente</dt><dd class="is-patient-detail"><?php echo esc_html( (string) ( $data['idade_paciente'] ?? '—' ) ); ?></dd>
										<dt class="is-patient-detail">Sexo do paciente</dt><dd class="is-patient-detail"><?php echo esc_html( $data['sexo_paciente'] ?? '—' ); ?></dd>
										<dt class="is-patient-detail">Gênero do paciente</dt><dd class="is-patient-detail"><?php echo esc_html( $data['genero_paciente'] ?? '—' ); ?></dd>
										<dt class="is-patient-detail">Doenças pré-existentes do paciente</dt><dd class="is-patient-detail"><?php $diseases = is_array( $data['doencas_preexistentes'] ?? null ) ? $data['doencas_preexistentes'] : array(); if ( in_array( 'Outro', $diseases, true ) && ! empty( $data['doencas_preexistentes_outro'] ) ) { $diseases[] = $data['doencas_preexistentes_outro']; } echo esc_html( $diseases ? implode( ', ', $diseases ) : '—' ); ?></dd>
										<dt class="is-patient-detail">Condições especiais do paciente</dt><dd class="is-patient-detail"><?php $special_conditions = is_array( $data['condicoes_especiais'] ?? null ) ? $data['condicoes_especiais'] : array(); if ( in_array( 'Outro', $special_conditions, true ) && ! empty( $data['condicoes_especiais_outro'] ) ) { $special_conditions[] = $data['condicoes_especiais_outro']; } echo esc_html( $special_conditions ? implode( ', ', $special_conditions ) : '—' ); ?></dd>
									<?php endif; ?>
									<dt>Consentiu em participar da pesquisa?</dt><dd><?php echo esc_html( $data['consentimento'] ?? '—' ); ?></dd>
									<dt>Escolha registrada em</dt><dd><?php echo esc_html( $data['consentimento_em'] ?? '—' ); ?></dd>
									<dt>Confirmação ao solicitante</dt><dd><?php echo esc_html( $email_labels[ $requester_email['status'] ?? '' ] ?? 'Pendente' ); ?></dd>
									<dt>Notificação à equipe</dt><dd><?php echo esc_html( $email_labels[ $team_email['status'] ?? '' ] ?? 'Pendente' ); ?></dd>
									<?php if ( $public_post_id ) : ?><dt>E-mail da resposta</dt><dd><?php echo esc_html( $email_labels[ $response_email['status'] ?? '' ] ?? 'Pendente' ); ?></dd><?php endif; ?>
											<?php if ( $direct_response_email ) : ?><dt>E-mail direto da resposta</dt><dd><?php echo esc_html( $email_labels[ $direct_response_email['status'] ?? '' ] ?? 'Pendente' ); ?></dd><?php endif; ?>
								</dl>
								<h3>Pergunta</h3><div class="fafar-cemed-answer"><?php echo wpautop( esc_html( get_the_content( null, false ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before wpautop. ?></div>
								<h3>Outras informações</h3><div class="fafar-cemed-answer"><?php echo wpautop( esc_html( $data['informacoes_adicionais'] ?? '—' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before wpautop. ?></div>
								<?php if ( $classifications || $additional_needed ) : ?>
									<h3>Classificação e informações adicionais da resposta</h3>
									<dl>
										<dt>Classificação da solicitação</dt><dd><?php if ( in_array( 'Outro', $classifications, true ) && $classifications_other ) { $classifications[] = $classifications_other; } echo esc_html( $classifications ? implode( ', ', $classifications ) : '—' ); ?></dd>
										<dt>Foram necessárias informações adicionais?</dt><dd><?php echo esc_html( $additional_needed ?: '—' ); ?></dd>
										<dt>Tipos de informação</dt><dd><?php if ( in_array( 'Outro', $additional_types, true ) && $additional_types_other ) { $additional_types[] = $additional_types_other; } echo esc_html( $additional_types ? implode( ', ', $additional_types ) : '—' ); ?></dd>
										<dt>Informações solicitadas e respostas obtidas</dt><dd><?php echo nl2br( esc_html( $additional_details ?: '—' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before nl2br. ?></dd>
									</dl>
								<?php endif; ?>
								<?php if ( $feedback ) : ?>
									<h3>Avaliação recebida</h3>
									<dl><dt>Dúvida esclarecida</dt><dd><?php echo esc_html( $feedback['duvida_esclarecida'] ?? '—' ); ?></dd><dt>Linguagem clara</dt><dd><?php echo esc_html( $feedback['linguagem_clara'] ?? '—' ); ?></dd><dt>Nota</dt><dd><?php echo esc_html( $feedback['nota'] ?? '—' ); ?> / 5</dd><dt>Comentários</dt><dd><?php echo nl2br( esc_html( $feedback['comentarios'] ?? '—' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before nl2br. ?></dd></dl>
								<?php endif; ?>
								<?php if ( is_user_logged_in() ) : ?>
									<form class="fafar-cemed-manage" id="cemed-responder-<?php echo esc_attr( $question_id ); ?>" method="post" action="<?php echo esc_url( fafar_cemed_management_url() ); ?>">
										<h3><?php echo $public_post_id ? 'Atualizar resposta publicada' : 'Responder pergunta'; ?></h3>
										<?php wp_nonce_field( 'fafar_cemed_manage_' . $question_id, 'fafar_cemed_manage_nonce' ); ?>
										<input type="hidden" name="fafar_cemed_manage_action" value="save">
										<input type="hidden" name="cemed_question_id" value="<?php echo esc_attr( $question_id ); ?>">
										<input type="hidden" name="cemed_page_number" value="<?php echo esc_attr( $page_number ); ?>">
						<label for="cemed-public-title-<?php echo esc_attr( $question_id ); ?>">Título da publicação</label>
						<input id="cemed-public-title-<?php echo esc_attr( $question_id ); ?>" name="cemed_public_title" type="text" maxlength="200" value="<?php echo esc_attr( $public_title ); ?>">
										<label for="cemed-public-question-<?php echo esc_attr( $question_id ); ?>">Pergunta para publicação</label>
										<?php wp_editor( $public_question, 'cemed-public-question-' . $question_id, array( 'textarea_name' => 'cemed_public_question', 'textarea_rows' => 5, 'media_buttons' => false, 'quicktags' => false, 'tinymce' => array( 'toolbar1' => 'bold,italic,bullist,numlist,link', 'toolbar2' => '', 'menubar' => false ) ) ); ?>
										<p class="fafar-cemed-help">Revise este texto antes de publicar. O nome, o CPF, o e-mail e os demais campos pessoais não entram no post.</p>
										<label for="cemed-resposta-<?php echo esc_attr( $question_id ); ?>">Resposta</label>
										<?php wp_editor( $answer, 'cemed-resposta-' . $question_id, array( 'textarea_name' => 'cemed_resposta', 'textarea_rows' => 6, 'media_buttons' => false, 'quicktags' => false, 'tinymce' => array( 'toolbar1' => 'bold,italic,bullist,numlist,link', 'toolbar2' => '', 'menubar' => false ) ) ); ?>
						<div class="fafar-cemed-manage-field">
							<span class="fafar-cemed-label" id="cemed-classificacao-label-<?php echo esc_attr( $question_id ); ?>">Classifique a solicitação (se necessário, mais de uma opção pode ser selecionada):</span>
							<div class="fafar-cemed-choices is-stacked" role="group" aria-labelledby="cemed-classificacao-label-<?php echo esc_attr( $question_id ); ?>">
								<?php foreach ( fafar_cemed_answer_classification_options() as $classification ) : ?><label><input type="checkbox" name="cemed_classificacao[]" value="<?php echo esc_attr( $classification ); ?>" <?php checked( in_array( $classification, $classifications, true ) ); ?>> <?php echo esc_html( $classification ); ?></label><?php endforeach; ?>
							</div>
							<div data-cemed-classification-other <?php echo in_array( 'Outro', $classifications, true ) ? '' : 'hidden'; ?>><label for="cemed-classificacao-outro-<?php echo esc_attr( $question_id ); ?>">Outra classificação</label><input id="cemed-classificacao-outro-<?php echo esc_attr( $question_id ); ?>" name="cemed_classificacao_outro" type="text" maxlength="1200" value="<?php echo esc_attr( $classifications_other ); ?>"></div>
						</div>
						<div class="fafar-cemed-manage-field"><span class="fafar-cemed-label" id="cemed-informacao-adicional-label-<?php echo esc_attr( $question_id ); ?>">Foram necessárias informações adicionais?</span><div class="fafar-cemed-choices" role="group" aria-labelledby="cemed-informacao-adicional-label-<?php echo esc_attr( $question_id ); ?>"><label><input type="radio" name="cemed_informacao_adicional" value="Não" <?php checked( $additional_needed, 'Não' ); ?>> Não</label><label><input type="radio" name="cemed_informacao_adicional" value="Sim" <?php checked( $additional_needed, 'Sim' ); ?>> Sim</label></div></div>
						<div class="fafar-cemed-manage-field">
							<span class="fafar-cemed-label" id="cemed-informacao-adicional-tipo-label-<?php echo esc_attr( $question_id ); ?>">Informações adicionais necessárias:</span>
							<div class="fafar-cemed-choices is-stacked" role="group" aria-labelledby="cemed-informacao-adicional-tipo-label-<?php echo esc_attr( $question_id ); ?>">
								<?php foreach ( fafar_cemed_additional_info_options() as $additional_type ) : ?><label><input type="checkbox" name="cemed_informacao_adicional_tipo[]" value="<?php echo esc_attr( $additional_type ); ?>" <?php checked( in_array( $additional_type, $additional_types, true ) ); ?>> <?php echo esc_html( $additional_type ); ?></label><?php endforeach; ?>
							</div>
							<div data-cemed-additional-other <?php echo in_array( 'Outro', $additional_types, true ) ? '' : 'hidden'; ?>><label for="cemed-informacao-adicional-outro-<?php echo esc_attr( $question_id ); ?>">Outro tipo de informação</label><input id="cemed-informacao-adicional-outro-<?php echo esc_attr( $question_id ); ?>" name="cemed_informacao_adicional_outro" type="text" maxlength="1200" value="<?php echo esc_attr( $additional_types_other ); ?>"></div>
						</div>
						<div data-cemed-additional-details <?php echo 'Sim' === $additional_needed ? '' : 'hidden'; ?>><label for="cemed-informacoes-adicionais-detalhes-<?php echo esc_attr( $question_id ); ?>">Quais as informações adicionais foram solicitadas?</label><p class="fafar-cemed-help">Transcrever as informações adicionais solicitadas e as respostas obtidas.</p><textarea id="cemed-informacoes-adicionais-detalhes-<?php echo esc_attr( $question_id ); ?>" name="cemed_informacoes_adicionais_detalhes" rows="4" maxlength="10000"><?php echo esc_textarea( $additional_details ); ?></textarea></div>
											<p class="fafar-cemed-help">Salvar rascunho registra os campos preenchidos. Responder ao usuário envia a pergunta e a resposta por e-mail, sem criar um post. Publicar cria ou atualiza o post com comentários abertos e envia os links da resposta e da avaliação. No dev, o envio real é desativado.</p>
										<div class="fafar-cemed-manage-actions">
							<button <?php echo ! empty( $fafar_cemed_admin ) ? 'class="button"' : ''; ?> type="submit" name="cemed_action" value="draft">Salvar rascunho</button>
							<button <?php echo ! empty( $fafar_cemed_admin ) ? 'class="button"' : ''; ?> type="submit" name="cemed_action" value="respond_user">Responder ao Usuário</button>
							<button <?php echo ! empty( $fafar_cemed_admin ) ? 'class="button button-primary"' : ''; ?> type="submit" name="cemed_action" value="publish"><?php echo $public_post_id ? 'Atualizar post publicado' : 'Publicar resposta'; ?></button>
										</div>
									</form>
								<?php elseif ( $answer ) : ?>
										<h3>Resposta registrada</h3><div class="fafar-cemed-answer"><?php echo wp_kses_post( wpautop( $answer ) ); ?></div>
								<?php endif; ?>
							</div>
						</details>
					<?php endwhile; wp_reset_postdata(); ?>
					<?php if ( $questions->max_num_pages > 1 ) : ?>
						<nav class="fafar-cemed-pagination" aria-label="Páginas de perguntas"><?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'pagina', '%#%', fafar_cemed_management_url() ), 'format' => '', 'current' => $page_number, 'total' => $questions->max_num_pages, 'prev_text' => '← Anterior', 'next_text' => 'Próxima →' ) ) ); ?></nav>
					<?php endif; ?>
				<?php else : ?>
					<p><?php echo $detail_id ? 'Pergunta não encontrada.' : 'Nenhuma pergunta recebida até agora.'; ?></p>
				<?php endif; ?>
				<?php endif; ?>
			</div>
		<?php if ( empty( $fafar_cemed_admin ) ) : ?></article><?php else : ?></div><?php endif; ?>
</div>

<?php if ( empty( $fafar_cemed_admin ) ) { get_footer(); } ?>
