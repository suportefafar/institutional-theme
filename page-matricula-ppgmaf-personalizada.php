<?php
/** Versão nativa e independente de plugins da página de matrícula do PPGMAF. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post();
	?>
	<article <?php post_class( 'single-content fafar-ppgmaf-page' ); ?> id="post-<?php the_ID(); ?>">
		<header class="entry-header"><h1 class="entry-title"><?php the_title(); ?></h1></header>
		<div class="entry-content">
			<?php the_content(); ?>
			<section class="fafar-ppgmaf-application" aria-labelledby="ppgmaf-form-title">
				<h2 id="ppgmaf-form-title">Formulário de matrícula</h2>
				<p>Preencha os dados abaixo e anexe a documentação em PDF. Cada arquivo pode ter até 8 MB.</p>
				<?php
				$state = isset( $_GET['matricula'] ) ? sanitize_key( wp_unslash( $_GET['matricula'] ) ) : '';
				$messages = array(
					'enviado' => array( 'success', 'Matrícula enviada. O protocolo foi registrado.' ),
					'sessao-expirada' => array( 'error', 'A sessão expirou. Atualize a página e tente novamente.' ),
					'campos-obrigatorios' => array( 'error', 'Confira os campos obrigatórios do formulário.' ),
					'opcao-invalida' => array( 'error', 'Uma das opções selecionadas é inválida.' ),
					'dados-invalidos' => array( 'error', 'Confira os dados informados e tente novamente.' ),
					'arquivo-obrigatorio' => array( 'error', 'Anexe todos os documentos obrigatórios. O limite é 8 MB por arquivo.' ),
					'arquivo-invalido' => array( 'error', 'Os anexos devem ser arquivos PDF válidos.' ),
					'erro-arquivos' => array( 'error', 'Não foi possível guardar os documentos. Tente novamente mais tarde.' ),
					'erro-salvar' => array( 'error', 'Não foi possível registrar a matrícula. Tente novamente mais tarde.' ),
				);
				if ( isset( $messages[ $state ] ) ) { echo '<div class="fafar-ppgmaf-notice is-' . esc_attr( $messages[ $state ][0] ) . '" role="status">' . esc_html( $messages[ $state ][1] ) . '</div>'; }
				?>
				<form class="fafar-ppgmaf-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
					<input type="hidden" name="action" value="fafar_ppgmaf_submit">
					<?php wp_nonce_field( 'fafar_ppgmaf_submit', 'fafar_ppgmaf_nonce' ); ?>
					<div class="fafar-ppgmaf-honeypot" aria-hidden="true"><label for="ppgmaf-website">Website</label><input id="ppgmaf-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
					<fieldset><legend>Identificação pessoal</legend>
						<?php fafar_ppgmaf_render_choice_field( 'Origem', 'Origem', array( 'Comunidade Externa', 'Servidor' ) ); ?>
						<?php fafar_ppgmaf_render_text_field( 'NomeCompleto', 'Nome Completo', 'text', true, 'name' ); ?>
						<?php fafar_ppgmaf_render_choice_field( 'Sexo', 'Sexo', array( 'Feminino', 'Masculino' ) ); ?>
						<?php fafar_ppgmaf_render_text_field( 'Email', 'E-mail', 'email', true, 'email' ); ?>
						<?php fafar_ppgmaf_render_text_field( 'Telefone', 'Telefone', 'tel', true, 'tel', '', '', 'phone' ); ?>
						<?php fafar_ppgmaf_render_text_field( 'DataDeNascimento', 'Data de nascimento', 'date', true, '', '1920-01-01', wp_date( 'Y-m-d' ) ); ?>
						<?php fafar_ppgmaf_render_choice_field( 'EstadoCivil', 'Estado Civil', array( 'Solteiro(a)', 'Casado(a)', 'Viúvo(a)', 'Separado(a)', 'Outros' ) ); ?>
						<?php fafar_ppgmaf_render_text_field( 'NomeMae', 'Nome da Mãe', 'text', true ); ?>
						<?php fafar_ppgmaf_render_text_field( 'NomePai', 'Nome do Pai', 'text', true ); ?>
						<?php fafar_ppgmaf_render_choice_field( 'Naturalidade', 'Naturalidade', array( 'Brasileiro Nato', 'Brasileiro Naturalizado', 'Estrangeiro' ) ); ?>
						<div class="fafar-ppgmaf-conditional" data-ppgmaf-show="brasileiro">
							<?php fafar_ppgmaf_render_text_field( 'CPF', 'CPF', 'text', true, '', '', '', 'cpf' ); ?>
							<?php fafar_ppgmaf_render_text_field( 'RG', 'RG', 'text', true ); ?>
							<?php fafar_ppgmaf_render_text_field( 'OrgaoEmissorRG', 'Órgão Emissor do RG', 'text', true ); ?>
							<?php fafar_ppgmaf_render_select_field( 'EstadoEmissorRG', 'Estado Emissor do RG', fafar_ppgmaf_states(), true, '', 'Selecione o estado' ); ?>
						</div>
						<div class="fafar-ppgmaf-conditional" data-ppgmaf-show="estrangeiro">
							<?php fafar_ppgmaf_render_text_field( 'NumeroPassaporte', 'Número do Passaporte', 'text', true ); ?>
							<?php fafar_ppgmaf_render_text_field( 'ValidadePassaporte', 'Validade do Passaporte', 'text', true ); ?>
							<?php fafar_ppgmaf_render_text_field( 'ClassificacaoPassaporte', 'Classificação do Passaporte', 'text', true ); ?>
							<?php fafar_ppgmaf_render_text_field( 'ConcessaoPassaporte', 'Concessão do Passaporte', 'text', true ); ?>
							<?php fafar_ppgmaf_render_text_field( 'ProrrogacaoEstadia', 'Prorrogação de Estadia', 'text', true ); ?>
						</div>
					</fieldset>
					<fieldset><legend>Endereço</legend>
						<?php fafar_ppgmaf_render_text_field( 'CEP', 'CEP', 'text', true, '', '', '', 'cep' ); ?>
						<?php fafar_ppgmaf_render_text_field( 'Logradouro', 'Logradouro', 'text', true ); ?>
						<?php fafar_ppgmaf_render_text_field( 'NumeroResidencia', 'Número', 'number', true, '', '1' ); ?>
						<?php fafar_ppgmaf_render_text_field( 'ComplementoResidencia', 'Complemento', 'text', false ); ?>
						<?php fafar_ppgmaf_render_text_field( 'Bairro', 'Bairro', 'text', true ); ?>
						<?php fafar_ppgmaf_render_text_field( 'Cidade', 'Cidade', 'text', true ); ?>
						<?php fafar_ppgmaf_render_select_field( 'Estado', 'Estado', fafar_ppgmaf_states(), true, 'MG' ); ?>
					</fieldset>
					<fieldset><legend>Informações acadêmicas</legend>
						<?php fafar_ppgmaf_render_text_field( 'AnoConclusao', 'Ano de conclusão da graduação', 'number', true, '', '1950', '2035', '', 'Ex.: 2015' ); ?>
						<?php fafar_ppgmaf_render_select_field( 'SemestreConclusao', 'Semestre de conclusão', array( '1º semestre', '2º semestre' ), true, '', 'Selecione' ); ?>
					</fieldset>
					<fieldset><legend>Documentação</legend><p class="fafar-ppgmaf-help">Tamanho máximo: 8 MB por arquivo. Formato PDF.</p>
						<?php fafar_ppgmaf_render_file_field( 'RequerimentoJustificativa', '<a href="https://www.farmacia.ufmg.br/wp-content/uploads/2025/02/Requerimento-de-Isolada.pdf" target="_blank" rel="noopener noreferrer">Requerimento</a> preenchido, assinado e com justificativa no verso' ); ?>
						<?php fafar_ppgmaf_render_file_field( 'CurriculoLattes', 'Currículo Lattes ou comum (não é necessário anexar comprovações)' ); ?>
						<?php fafar_ppgmaf_render_file_field( 'DiplomaGraducao', 'Diploma de graduação' ); ?>
						<?php fafar_ppgmaf_render_file_field( 'HistoricoGraducao', 'Histórico de graduação' ); ?>
						<?php fafar_ppgmaf_render_file_field( 'ComprovanteEndereco', 'Comprovante de endereço' ); ?>
						<div class="fafar-ppgmaf-conditional" data-ppgmaf-show="brasileiro"><?php fafar_ppgmaf_render_file_field( 'ArquivoIdentidade', 'CPF (CNH ou RG com CPF)' ); ?></div>
						<div class="fafar-ppgmaf-conditional" data-ppgmaf-show="estrangeiro"><?php fafar_ppgmaf_render_file_field( 'PassaporteVisto', 'Cópia do passaporte com visto temporário de estudante ou permanente válido' ); ?></div>
						<div class="fafar-ppgmaf-conditional" data-ppgmaf-show="servidor"><?php fafar_ppgmaf_render_file_field( 'CopiaContracheque', 'Cópia do último contracheque' ); ?><?php fafar_ppgmaf_render_file_field( 'AutorizacaoChefia', 'Autorização da chefia' ); ?></div>
					</fieldset>
					<button class="ffds-btn ffds-btn-primary" type="submit">Enviar matrícula</button>
				</form>
			</section>
		</div>
	</article>
	<?php
endwhile;
get_footer();

function fafar_ppgmaf_render_text_field( $name, $label, $type = 'text', $required = false, $autocomplete = '', $min = '', $max = '', $class = '', $placeholder = '' ) {
	$id = 'ppgmaf-' . sanitize_html_class( $name );
	echo '<p class="fafar-ppgmaf-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . ( $required ? ' <span aria-hidden="true">*</span>' : '' ) . '</label><input id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '"' . ( $required ? ' required' : '' ) . ( $autocomplete ? ' autocomplete="' . esc_attr( $autocomplete ) . '"' : '' ) . ( $min !== '' ? ' min="' . esc_attr( $min ) . '"' : '' ) . ( $max !== '' ? ' max="' . esc_attr( $max ) . '"' : '' ) . ( $class ? ' data-ppgmaf-mask="' . esc_attr( $class ) . '"' : '' ) . ( $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ) . '></p>';
}

function fafar_ppgmaf_render_choice_field( $name, $label, $options ) {
	echo '<fieldset class="fafar-ppgmaf-choice"><legend>' . esc_html( $label ) . ' <span aria-hidden="true">*</span></legend>';
	foreach ( $options as $index => $option ) { echo '<label><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $option ) . '"' . ( 0 === $index ? ' required checked' : '' ) . '> ' . esc_html( $option ) . '</label>'; }
	echo '</fieldset>';
}

function fafar_ppgmaf_render_select_field( $name, $label, $options, $required = false, $selected = '', $placeholder = '' ) {
	$id = 'ppgmaf-' . sanitize_html_class( $name );
	echo '<p class="fafar-ppgmaf-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . ( $required ? ' <span aria-hidden="true">*</span>' : '' ) . '</label><select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . ( $required ? ' required' : '' ) . '>';
	if ( $placeholder ) { echo '<option value="">' . esc_html( $placeholder ) . '</option>'; }
	foreach ( $options as $option ) { echo '<option value="' . esc_attr( $option ) . '"' . selected( $selected, $option, false ) . '>' . esc_html( $option ) . '</option>'; }
	echo '</select></p>';
}

function fafar_ppgmaf_render_file_field( $name, $label ) {
	$id = 'ppgmaf-' . sanitize_html_class( $name );
	echo '<p class="fafar-ppgmaf-field fafar-ppgmaf-file"><label for="' . esc_attr( $id ) . '">' . wp_kses( $label, array( 'a' => array( 'href' => true, 'target' => true, 'rel' => true ) ) ) . ' <span aria-hidden="true">*</span></label><input id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" type="file" accept=".pdf,application/pdf" data-ppgmaf-file required></p>';
}
