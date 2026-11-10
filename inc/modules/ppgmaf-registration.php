<?php
/** Matrícula para disciplina isolada do PPGMAF, sem dependência de plugins. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', 'fafar_ppgmaf_register_application_type', 5 );
add_action( 'admin_menu', 'fafar_ppgmaf_register_admin_page' );
add_action( 'admin_post_fafar_ppgmaf_submit', 'fafar_ppgmaf_submit' );
add_action( 'admin_post_nopriv_fafar_ppgmaf_submit', 'fafar_ppgmaf_submit' );
add_action( 'admin_post_fafar_ppgmaf_set_status', 'fafar_ppgmaf_set_status' );
add_action( 'admin_post_fafar_ppgmaf_download', 'fafar_ppgmaf_download' );
add_action( 'init', 'fafar_register_retired_cf7_notice', 100 );

/** Evita exibir shortcodes antigos como texto depois da remoção do CF7. */
function fafar_register_retired_cf7_notice() {
	if ( function_exists( 'wpcf7_contact_form_tag_func' ) || shortcode_exists( 'contact-form-7' ) ) { return; }
	add_shortcode( 'contact-form-7', 'fafar_retired_cf7_notice' );
}

function fafar_retired_cf7_notice() {
	return '<p class="dynamic-notice dynamic-notice-info" role="status">Este formulário foi desativado. Entre em contato com a secretaria responsável.</p>';
}

function fafar_ppgmaf_register_application_type() {
	register_post_type( 'ppgmaf_app', array(
		'labels' => array( 'name' => 'Matrículas PPGMAF', 'singular_name' => 'Matrícula PPGMAF' ),
		'public' => false, 'publicly_queryable' => false, 'exclude_from_search' => true,
		'show_ui' => false, 'show_in_rest' => false, 'supports' => array( 'title', 'author' ),
		'capability_type' => 'post', 'map_meta_cap' => true,
	) );
}

function fafar_ppgmaf_register_admin_page() {
	add_menu_page( 'Matrículas PPGMAF', 'Matrículas PPGMAF', 'manage_options', 'fafar-ppgmaf-applications', 'fafar_ppgmaf_render_admin_page', 'dashicons-welcome-learn-more', 27 );
}

function fafar_ppgmaf_field_labels() {
	return array(
		'Origem' => 'Origem', 'NomeCompleto' => 'Nome completo', 'Sexo' => 'Sexo', 'Email' => 'E-mail', 'Telefone' => 'Telefone',
		'DataDeNascimento' => 'Data de nascimento', 'EstadoCivil' => 'Estado civil', 'NomeMae' => 'Nome da mãe', 'NomePai' => 'Nome do pai',
		'Naturalidade' => 'Naturalidade', 'CPF' => 'CPF', 'RG' => 'RG', 'OrgaoEmissorRG' => 'Órgão emissor do RG',
		'EstadoEmissorRG' => 'Estado emissor do RG', 'NumeroPassaporte' => 'Número do passaporte', 'ValidadePassaporte' => 'Validade do passaporte',
		'ClassificacaoPassaporte' => 'Classificação do passaporte', 'ConcessaoPassaporte' => 'Concessão do passaporte', 'ProrrogacaoEstadia' => 'Prorrogação de estadia',
		'CEP' => 'CEP', 'Logradouro' => 'Logradouro', 'NumeroResidencia' => 'Número', 'ComplementoResidencia' => 'Complemento',
		'Bairro' => 'Bairro', 'Cidade' => 'Cidade', 'Estado' => 'Estado', 'AnoConclusao' => 'Ano de conclusão da graduação',
		'SemestreConclusao' => 'Semestre de conclusão',
	);
}

function fafar_ppgmaf_radio_options() {
	return array(
		'Origem' => array( 'Comunidade Externa', 'Servidor' ), 'Sexo' => array( 'Feminino', 'Masculino' ),
		'EstadoCivil' => array( 'Solteiro(a)', 'Casado(a)', 'Viúvo(a)', 'Separado(a)', 'Outros' ),
		'Naturalidade' => array( 'Brasileiro Nato', 'Brasileiro Naturalizado', 'Estrangeiro' ),
		'SemestreConclusao' => array( '1º semestre', '2º semestre' ),
	);
}

function fafar_ppgmaf_states() {
	return array( 'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO' );
}

function fafar_ppgmaf_upload_labels() {
	return array(
		'RequerimentoJustificativa' => 'Requerimento preenchido, assinado e com justificativa no verso',
		'CurriculoLattes' => 'Currículo Lattes ou comum', 'DiplomaGraducao' => 'Diploma de graduação',
		'HistoricoGraducao' => 'Histórico de graduação', 'ComprovanteEndereco' => 'Comprovante de endereço',
		'ArquivoIdentidade' => 'CPF (CNH ou RG com CPF)', 'PassaporteVisto' => 'Passaporte com visto válido',
		'CopiaContracheque' => 'Último contracheque', 'AutorizacaoChefia' => 'Autorização da chefia',
	);
}

function fafar_ppgmaf_private_upload_dir() {
	$uploads = wp_upload_dir();
	return trailingslashit( $uploads['basedir'] ) . 'fafar-ppgmaf-submissions';
}

function fafar_ppgmaf_protect_upload_dir( $directory ) {
	if ( ! wp_mkdir_p( $directory ) ) { return false; }
	$rules = "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n";
	$guard = trailingslashit( $directory ) . '.htaccess';
	if ( ! file_exists( $guard ) && false === file_put_contents( $guard, $rules, LOCK_EX ) ) { return false; }
	$index = trailingslashit( $directory ) . 'index.php';
	if ( ! file_exists( $index ) && false === file_put_contents( $index, "<?php http_response_code(404); exit;\n", LOCK_EX ) ) { return false; }
	return is_writable( $directory );
}

function fafar_ppgmaf_redirect_form( $state ) {
	$url = wp_get_referer();
	if ( ! $url ) { $url = home_url( '/matricula-disciplina-isolada-ppgmaf-personalizada/' ); }
	wp_safe_redirect( add_query_arg( 'matricula', $state, remove_query_arg( 'matricula', $url ) ) );
	exit;
}

function fafar_ppgmaf_submit() {
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['fafar_ppgmaf_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fafar_ppgmaf_nonce'] ) ), 'fafar_ppgmaf_submit' ) ) {
		fafar_ppgmaf_redirect_form( 'sessao-expirada' );
	}
	if ( ! empty( $_POST['website'] ) ) { fafar_ppgmaf_redirect_form( 'enviado' ); }

	$labels = fafar_ppgmaf_field_labels();
	$radio = fafar_ppgmaf_radio_options();
	$states = fafar_ppgmaf_states();
	$data = array();
	foreach ( $labels as $key => $label ) {
		$value = isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? trim( (string) wp_unslash( $_POST[ $key ] ) ) : '';
		$data[ $key ] = 'Email' === $key ? sanitize_email( $value ) : sanitize_text_field( $value );
	}
	$required = array_keys( $labels );
	$required = array_values( array_diff( $required, array( 'ComplementoResidencia', 'CPF', 'RG', 'OrgaoEmissorRG', 'EstadoEmissorRG', 'NumeroPassaporte', 'ValidadePassaporte', 'ClassificacaoPassaporte', 'ConcessaoPassaporte', 'ProrrogacaoEstadia' ) ) );
	$foreign = 'Estrangeiro' === $data['Naturalidade'];
	if ( $foreign ) { $required = array_merge( $required, array( 'NumeroPassaporte', 'ValidadePassaporte', 'ClassificacaoPassaporte', 'ConcessaoPassaporte', 'ProrrogacaoEstadia' ) ); }
	else { $required = array_merge( $required, array( 'CPF', 'RG', 'OrgaoEmissorRG', 'EstadoEmissorRG' ) ); }
	foreach ( $required as $key ) { if ( '' === ( $data[ $key ] ?? '' ) ) { fafar_ppgmaf_redirect_form( 'campos-obrigatorios' ); } }
	foreach ( $radio as $key => $options ) { if ( ! in_array( $data[ $key ], $options, true ) ) { fafar_ppgmaf_redirect_form( 'opcao-invalida' ); } }
	if ( ! is_email( $data['Email'] ) || ! in_array( $data['Estado'], $states, true ) || ( ! $foreign && ! in_array( $data['EstadoEmissorRG'], $states, true ) ) ) { fafar_ppgmaf_redirect_form( 'dados-invalidos' ); }
	if ( ! ctype_digit( $data['AnoConclusao'] ) || (int) $data['AnoConclusao'] < 1950 || (int) $data['AnoConclusao'] > 2035 ) { fafar_ppgmaf_redirect_form( 'dados-invalidos' ); }
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data['DataDeNascimento'] ) || strtotime( $data['DataDeNascimento'] ) < strtotime( '1920-01-01' ) || strtotime( $data['DataDeNascimento'] ) > current_time( 'timestamp' ) ) { fafar_ppgmaf_redirect_form( 'dados-invalidos' ); }
	if ( ! ctype_digit( $data['NumeroResidencia'] ) || (int) $data['NumeroResidencia'] < 1 ) { fafar_ppgmaf_redirect_form( 'dados-invalidos' ); }

	$upload_labels = fafar_ppgmaf_upload_labels();
	$required_files = array( 'RequerimentoJustificativa', 'CurriculoLattes', 'DiplomaGraducao', 'HistoricoGraducao', 'ComprovanteEndereco' );
	$required_files[] = $foreign ? 'PassaporteVisto' : 'ArquivoIdentidade';
	if ( 'Servidor' === $data['Origem'] ) { $required_files = array_merge( $required_files, array( 'CopiaContracheque', 'AutorizacaoChefia' ) ); }
	$submission_id = wp_generate_uuid4();
	$directory = trailingslashit( fafar_ppgmaf_private_upload_dir() ) . $submission_id;
	if ( ! fafar_ppgmaf_protect_upload_dir( $directory ) ) { fafar_ppgmaf_redirect_form( 'erro-arquivos' ); }
	$files = array();
	foreach ( $required_files as $field ) {
		$file = $_FILES[ $field ] ?? null;
		if ( ! is_array( $file ) || UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) || (int) $file['size'] > 8 * MB_IN_BYTES ) { fafar_ppgmaf_remove_uploads( $directory ); fafar_ppgmaf_redirect_form( 'arquivo-obrigatorio' ); }
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], array( 'pdf' => 'application/pdf' ) );
		$mime = function_exists( 'finfo_open' ) ? finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $file['tmp_name'] ) : '';
		if ( 'pdf' !== ( $check['ext'] ?? '' ) || 'application/pdf' !== $mime || ! is_uploaded_file( $file['tmp_name'] ) ) { fafar_ppgmaf_remove_uploads( $directory ); fafar_ppgmaf_redirect_form( 'arquivo-invalido' ); }
		$filename = sanitize_key( $field ) . '.pdf';
		if ( ! move_uploaded_file( $file['tmp_name'], trailingslashit( $directory ) . $filename ) ) { fafar_ppgmaf_remove_uploads( $directory ); fafar_ppgmaf_redirect_form( 'erro-arquivos' ); }
		$files[ $field ] = $filename;
	}
	$data['files'] = $files;
	$post_id = wp_insert_post( array( 'post_type' => 'ppgmaf_app', 'post_status' => 'publish', 'post_title' => $data['NomeCompleto'], 'post_author' => 0 ), true );
	if ( is_wp_error( $post_id ) ) { fafar_ppgmaf_remove_uploads( $directory ); fafar_ppgmaf_redirect_form( 'erro-salvar' ); }
	update_post_meta( $post_id, '_fafar_ppgmaf_data', $data );
	update_post_meta( $post_id, '_fafar_ppgmaf_files', $files );
	update_post_meta( $post_id, '_fafar_ppgmaf_submitted_at', current_time( 'mysql' ) );
	fafar_ppgmaf_redirect_form( 'enviado' );
}

function fafar_ppgmaf_remove_uploads( $directory ) {
	if ( ! is_dir( $directory ) ) { return; }
	foreach ( glob( trailingslashit( $directory ) . '*' ) ?: array() as $file ) { if ( is_file( $file ) ) { wp_delete_file( $file ); } }
	@rmdir( $directory );
}

function fafar_ppgmaf_admin_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => 'fafar-ppgmaf-applications' ), $args ), admin_url( 'admin.php' ) );
}

function fafar_ppgmaf_set_status() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Acesso negado.', 403 ); }
	$id = isset( $_POST['application_id'] ) ? absint( $_POST['application_id'] ) : 0;
	$action = isset( $_POST['application_action'] ) ? sanitize_key( wp_unslash( $_POST['application_action'] ) ) : '';
	check_admin_referer( 'fafar_ppgmaf_status_' . $id );
	if ( $id && 'ppgmaf_app' === get_post_type( $id ) && in_array( $action, array( 'archive', 'restore' ), true ) ) {
		wp_update_post( array( 'ID' => $id, 'post_status' => 'archive' === $action ? 'draft' : 'publish' ) );
	}
	wp_safe_redirect( fafar_ppgmaf_admin_url( array( 'application_id' => $id ) ) );
	exit;
}

function fafar_ppgmaf_download() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Acesso negado.', 403 ); }
	$id = isset( $_GET['application_id'] ) ? absint( $_GET['application_id'] ) : 0;
	$field = isset( $_GET['field'] ) ? sanitize_key( wp_unslash( $_GET['field'] ) ) : '';
	check_admin_referer( 'fafar_ppgmaf_download_' . $id . '_' . $field );
	$files = get_post_meta( $id, '_fafar_ppgmaf_files', true );
	if ( 'ppgmaf_app' !== get_post_type( $id ) || ! is_array( $files ) || empty( $files[ $field ] ) ) { wp_die( 'Arquivo não encontrado.', 404 ); }
	$path = realpath( trailingslashit( fafar_ppgmaf_private_upload_dir() ) . $id . '/' . basename( $files[ $field ] ) );
	$root = realpath( trailingslashit( fafar_ppgmaf_private_upload_dir() ) . $id );
	if ( ! $path || ! $root || 0 !== strpos( $path, trailingslashit( $root ) ) || ! is_file( $path ) ) { wp_die( 'Arquivo não encontrado.', 404 ); }
	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $field . '.pdf' ) . '"' );
	readfile( $path );
	exit;
}

function fafar_ppgmaf_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Acesso negado.', 403 ); }
	$id = isset( $_GET['application_id'] ) ? absint( $_GET['application_id'] ) : 0;
	echo '<div class="wrap"><h1>Matrículas para disciplina isolada — PPGMAF</h1>';
	if ( $id ) { fafar_ppgmaf_render_application_detail( $id ); echo '</div>'; return; }
	$page = max( 1, absint( $_GET['paged'] ?? 1 ) );
	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$args = array( 'post_type' => 'ppgmaf_app', 'post_status' => array( 'publish', 'draft' ), 'posts_per_page' => 25, 'paged' => $page, 'orderby' => 'date', 'order' => 'DESC' );
	if ( $search ) { $args['s'] = $search; }
	$query = new WP_Query( $args );
	echo '<p>Dados pessoais e documentos enviados são visíveis somente a administradores.</p><form method="get"><input type="hidden" name="page" value="fafar-ppgmaf-applications"><p class="search-box"><label class="screen-reader-text" for="ppgmaf-search">Buscar</label><input id="ppgmaf-search" type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="Nome do candidato"><button class="button">Buscar</button></p></form>';
	echo '<table class="widefat striped"><thead><tr><th>Nome</th><th>E-mail</th><th>Recebida em</th><th>Situação</th><th>Ação</th></tr></thead><tbody>';
	if ( $query->have_posts() ) {
		foreach ( $query->posts as $application ) {
			$data = get_post_meta( $application->ID, '_fafar_ppgmaf_data', true );
			echo '<tr><th><a href="' . esc_url( fafar_ppgmaf_admin_url( array( 'application_id' => $application->ID ) ) ) . '">' . esc_html( $data['NomeCompleto'] ?? $application->post_title ) . '</a></th><td>' . esc_html( $data['Email'] ?? '' ) . '</td><td>' . esc_html( get_post_meta( $application->ID, '_fafar_ppgmaf_submitted_at', true ) ) . '</td><td>' . ( 'publish' === $application->post_status ? 'Ativa' : 'Arquivada' ) . '</td><td><a class="button button-small" href="' . esc_url( fafar_ppgmaf_admin_url( array( 'application_id' => $application->ID ) ) ) . '">Visualizar</a></td></tr>';
		}
	} else { echo '<tr><td colspan="5">Nenhuma matrícula encontrada.</td></tr>'; }
	echo '</tbody></table>';
	$pagination = paginate_links( array( 'base' => add_query_arg( array( 'page' => 'fafar-ppgmaf-applications', 'paged' => '%#%', 's' => $search ), admin_url( 'admin.php' ) ), 'format' => '', 'current' => $page, 'total' => max( 1, (int) $query->max_num_pages ) ) );
	if ( $pagination ) { echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $pagination ) . '</div></div>'; }
	wp_reset_postdata();
	echo '</div>';
}

function fafar_ppgmaf_render_application_detail( $id ) {
	$post = get_post( $id );
	if ( ! $post || 'ppgmaf_app' !== $post->post_type ) { echo '<p>Matrícula não encontrada.</p></div>'; return; }
	$data = get_post_meta( $id, '_fafar_ppgmaf_data', true );
	$files = get_post_meta( $id, '_fafar_ppgmaf_files', true );
	echo '<p><a href="' . esc_url( fafar_ppgmaf_admin_url() ) . '">← Todas as matrículas</a></p><h2>' . esc_html( $data['NomeCompleto'] ?? $post->post_title ) . '</h2><p>Recebida em ' . esc_html( get_post_meta( $id, '_fafar_ppgmaf_submitted_at', true ) ) . ' · ' . ( 'publish' === $post->post_status ? 'Ativa' : 'Arquivada' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="fafar_ppgmaf_set_status"><input type="hidden" name="application_id" value="' . esc_attr( $id ) . '"><input type="hidden" name="application_action" value="' . ( 'publish' === $post->post_status ? 'archive' : 'restore' ) . '">';
	wp_nonce_field( 'fafar_ppgmaf_status_' . $id );
	echo '<button class="button">' . ( 'publish' === $post->post_status ? 'Arquivar matrícula' : 'Restaurar matrícula' ) . '</button></form><table class="widefat striped" style="max-width:900px;margin-top:1rem"><tbody>';
	foreach ( fafar_ppgmaf_field_labels() as $key => $label ) { if ( isset( $data[ $key ] ) ) { echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( is_scalar( $data[ $key ] ) ? $data[ $key ] : implode( ', ', (array) $data[ $key ] ) ) . '</td></tr>'; } }
	echo '</tbody></table><h2>Documentos anexados</h2><ul>';
	foreach ( (array) $files as $field => $filename ) {
		$label = fafar_ppgmaf_upload_labels()[ $field ] ?? $field;
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=fafar_ppgmaf_download&application_id=' . $id . '&field=' . rawurlencode( $field ) ), 'fafar_ppgmaf_download_' . $id . '_' . $field );
		echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . ' (PDF)</a></li>';
	}
	echo '</ul>';
}
