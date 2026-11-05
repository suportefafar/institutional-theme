<?php
/** Formulário permanente de perguntas ao Cemed. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

nocache_headers();
$values = fafar_cemed_form_values();
$result = fafar_cemed_handle_submission( $values );
$style  = get_stylesheet_directory() . '/assets/css/cemed.css';
$script = get_stylesheet_directory() . '/assets/js/cemed-masks.js';
wp_enqueue_style( 'fafar-cemed', get_stylesheet_directory_uri() . '/assets/css/cemed.css', array( 'fafar-theme' ), file_exists( $style ) ? filemtime( $style ) : '1.0.0' );
wp_enqueue_script( 'fafar-cemed-masks', get_stylesheet_directory_uri() . '/assets/js/cemed-masks.js', array(), file_exists( $script ) ? filemtime( $script ) : '1.0.0', true );
get_header();
?>

<div id="primary" class="content-area">
	<?php while ( have_posts() ) : the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header"><?php the_title( '<h1 class="entry-title">', '</h1>' ); ?></header>
			<div class="entry-content clear fafar-cemed-page">
				<?php do_action( 'fafar_entry_content_before' ); ?>
				<h2>Solicitação de informação — Cemed UFMG</h2>
				<p>O Serviço de Informação sobre Medicamentos (SIM) do Cemed é um serviço gratuito e tem o objetivo de esclarecer dúvidas relacionadas a medicamentos para promoção do seu uso correto. Nossas orientações não substituem uma consulta ou avaliação presencial com um profissional de saúde.</p>
				<p>Para que possamos atender a sua solicitação, pedimos que preencha o formulário a seguir, apresentando as informações de maneira clara e detalhada. Seus dados pessoais e de contato serão tratados de maneira confidencial. Após a resposta, a pergunta poderá ser revisada pela equipe e publicada sem esses dados, junto com a resposta, em uma página aberta a comentários.</p>
				<p>Evite incluir nomes, documentos ou outros dados que identifiquem pacientes no texto da pergunta.</p>
				<p class="fafar-cemed-alert"><strong>Atenção:</strong> não emitimos pareceres técnicos para fins judiciais e não atendemos solicitações relativas a monografias, dissertações, teses ou outros trabalhos acadêmicos ou similares.</p>
				<?php if ( isset( $_GET['enviada'] ) && '1' === $_GET['enviada'] ) : ?>
					<?php $reference = isset( $_GET['referencia'] ) && is_scalar( $_GET['referencia'] ) ? sanitize_text_field( wp_unslash( $_GET['referencia'] ) ) : ''; ?>
					<p class="fafar-cemed-message is-success" role="status">Sua pergunta foi recebida com sucesso. <?php if ( preg_match( '/^CEMEDUFMG-\d{10,}$/', $reference ) ) : ?>Código: <strong><?php echo esc_html( $reference ); ?></strong>.<?php endif; ?></p>
				<?php endif; ?>
				<?php if ( is_wp_error( $result ) ) : ?>
					<p class="fafar-cemed-message is-error" role="alert"><?php echo esc_html( $result->get_error_message() ); ?></p>
				<?php endif; ?>
				<form class="fafar-cemed-form" method="post" action="<?php echo esc_url( get_permalink() ); ?>" data-cemed-nonce-url="<?php echo esc_url( admin_url( 'admin-ajax.php?action=fafar_cemed_form_nonce' ) ); ?>">
					<?php wp_nonce_field( 'fafar_cemed_submit', 'fafar_cemed_nonce' ); ?>
					<input type="hidden" name="fafar_cemed_action" value="fafar_cemed_submit">
					<div class="fafar-cemed-honeypot" aria-hidden="true"><label for="cemed-website">Deixe este campo vazio</label><input id="cemed-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
					<p>Os campos marcados com <span aria-hidden="true">*</span> são obrigatórios.</p>
					<fieldset>
						<legend>Seus dados</legend>
						<div class="fafar-cemed-grid">
							<div class="fafar-cemed-field"><label for="cemed-nome">Nome <span aria-hidden="true">*</span></label><input id="cemed-nome" name="nome" type="text" autocomplete="name" maxlength="200" value="<?php echo esc_attr( $values['nome'] ); ?>" required></div>
							<div class="fafar-cemed-field"><label for="cemed-email">E-mail <span aria-hidden="true">*</span></label><input id="cemed-email" name="email" type="email" autocomplete="email" maxlength="254" value="<?php echo esc_attr( $values['email'] ); ?>" required></div>
							<div class="fafar-cemed-field"><label for="cemed-cpf">CPF</label><input id="cemed-cpf" name="cpf" type="text" inputmode="numeric" autocomplete="off" maxlength="14" placeholder="000.000.000-00" data-cemed-mask="cpf" value="<?php echo esc_attr( $values['cpf'] ); ?>"></div>
							<div class="fafar-cemed-field"><label for="cemed-telefone">Telefone para contato</label><input id="cemed-telefone" name="telefone" type="tel" inputmode="tel" autocomplete="tel" maxlength="15" placeholder="(00) 00000-0000" data-cemed-mask="telefone" value="<?php echo esc_attr( $values['telefone'] ); ?>"></div>
							<div class="fafar-cemed-field"><label for="cemed-uf">UF</label><select id="cemed-uf" name="uf" autocomplete="address-level1"><option value="">Selecione</option><?php foreach ( fafar_ppgcf_brazilian_states() as $uf => $state_name ) : ?><option value="<?php echo esc_attr( $uf ); ?>" <?php selected( $values['uf'], $uf ); ?>><?php echo esc_html( $uf . ' — ' . $state_name ); ?></option><?php endforeach; ?></select></div>
							<div class="fafar-cemed-field"><label for="cemed-municipio">Município</label><input id="cemed-municipio" name="municipio" type="text" autocomplete="address-level2" maxlength="120" value="<?php echo esc_attr( $values['municipio'] ); ?>"></div>
						</div>
						<div class="fafar-cemed-field"><span class="fafar-cemed-label" id="cemed-profissional-label">Profissional da saúde? <span aria-hidden="true">*</span></span><div class="fafar-cemed-choices" role="group" aria-labelledby="cemed-profissional-label"><label><input type="radio" name="profissional_saude" value="Sim" <?php checked( $values['profissional_saude'], 'Sim' ); ?> required> Sim</label><label><input type="radio" name="profissional_saude" value="Não" <?php checked( $values['profissional_saude'], 'Não' ); ?> required> Não</label></div></div>
						<div class="fafar-cemed-field" data-cemed-profissao-wrap <?php echo 'Sim' === $values['profissional_saude'] ? '' : 'hidden'; ?>><span class="fafar-cemed-label" id="cemed-profissao-label">Qual é a sua profissão? <span aria-hidden="true">*</span></span><div class="fafar-cemed-choices" role="group" aria-labelledby="cemed-profissao-label">
							<?php foreach ( array( 'Dentista', 'Enfermeiro', 'Farmacêutico', 'Médico', 'Outro' ) as $profissao ) : ?><label><input type="radio" name="profissao_saude" value="<?php echo esc_attr( $profissao ); ?>" <?php checked( $values['profissao_saude'], $profissao ); ?>> <?php echo esc_html( $profissao ); ?></label><?php endforeach; ?>
						</div></div>
						<div class="fafar-cemed-field" data-cemed-profissao-outro-wrap <?php echo 'Outro' === $values['profissao_saude'] && 'Sim' === $values['profissional_saude'] ? '' : 'hidden'; ?>><label for="cemed-profissao-outro">Informe sua profissão <span aria-hidden="true">*</span></label><input id="cemed-profissao-outro" name="profissao_saude_outro" type="text" maxlength="120" value="<?php echo esc_attr( $values['profissao_saude_outro'] ); ?>"></div>
					</fieldset>
					<fieldset>
						<legend>Sua solicitação</legend>
						<div class="fafar-cemed-field"><label for="cemed-pergunta">Qual a sua pergunta? <span aria-hidden="true">*</span></label><p class="fafar-cemed-help">Procure ser o mais claro possível ao elaborar sua pergunta. Ofereça informações detalhadas e seja fiel à situação real.</p><textarea id="cemed-pergunta" name="pergunta" rows="7" maxlength="10000" required><?php echo esc_textarea( $values['pergunta'] ); ?></textarea></div>
						<div class="fafar-cemed-field"><span class="fafar-cemed-label" id="cemed-paciente-label">A sua pergunta envolve um paciente específico? <span aria-hidden="true">*</span></span><p class="fafar-cemed-help">Esse paciente pode ser você mesmo, um familiar ou amigo. Se você for um profissional de saúde, ele pode ser um indivíduo que está sob seus cuidados.</p><div class="fafar-cemed-choices" role="group" aria-labelledby="cemed-paciente-label"><label><input type="radio" name="paciente_especifico" value="Sim" <?php checked( $values['paciente_especifico'], 'Sim' ); ?> required> Sim</label><label><input type="radio" name="paciente_especifico" value="Não" <?php checked( $values['paciente_especifico'], 'Não' ); ?> required> Não</label></div></div>
						<section class="fafar-cemed-patient" data-cemed-patient-section <?php echo 'Sim' === $values['paciente_especifico'] ? '' : 'hidden'; ?> aria-labelledby="cemed-patient-title">
							<h3 id="cemed-patient-title">Informações do Paciente</h3>
							<p class="fafar-cemed-help">As perguntas abaixo são relacionadas ao paciente envolvido na pergunta. Elas são importantes, pois vão direcionar a nossa busca por informações sobre o tema e podem impactar diretamente na resposta.</p>
							<h4>Dados Gerais do Paciente</h4>
							<div class="fafar-cemed-grid">
								<div class="fafar-cemed-field"><label for="cemed-paciente-idade">Idade <span aria-hidden="true">*</span></label><input id="cemed-paciente-idade" name="idade_paciente" type="number" min="0" max="130" step="1" value="<?php echo esc_attr( $values['idade_paciente'] ); ?>"></div>
								<div class="fafar-cemed-field"><label for="cemed-paciente-sexo">Sexo <span aria-hidden="true">*</span></label><select id="cemed-paciente-sexo" name="sexo_paciente"><option value="">Selecione</option><?php foreach ( array( 'Feminino', 'Masculino' ) as $sexo ) : ?><option value="<?php echo esc_attr( $sexo ); ?>" <?php selected( $values['sexo_paciente'], $sexo ); ?>><?php echo esc_html( $sexo ); ?></option><?php endforeach; ?></select></div>
								<div class="fafar-cemed-field"><label for="cemed-paciente-genero">Gênero <span aria-hidden="true">*</span></label><select id="cemed-paciente-genero" name="genero_paciente"><option value="">Selecione</option><?php foreach ( array( 'Cisgênero', 'Transgênero', 'Não Binário', 'Prefiro Não Responder' ) as $genero ) : ?><option value="<?php echo esc_attr( $genero ); ?>" <?php selected( $values['genero_paciente'], $genero ); ?>><?php echo esc_html( $genero ); ?></option><?php endforeach; ?></select></div>
							</div>
							<h4>Condições clínicas do paciente</h4>
							<div class="fafar-cemed-field"><span class="fafar-cemed-label" id="cemed-doencas-label">Doenças pré-existentes <span aria-hidden="true">*</span></span><div class="fafar-cemed-choices is-stacked" role="group" aria-labelledby="cemed-doencas-label">
								<?php foreach ( fafar_cemed_patient_disease_options() as $doenca ) : ?><label><input type="checkbox" name="doencas_preexistentes[]" value="<?php echo esc_attr( $doenca ); ?>" <?php checked( in_array( $doenca, $values['doencas_preexistentes'], true ) ); ?>> <?php echo esc_html( $doenca ); ?></label><?php endforeach; ?>
							</div></div>
							<div class="fafar-cemed-field" data-cemed-diseases-other-wrap <?php echo in_array( 'Outro', $values['doencas_preexistentes'], true ) && 'Sim' === $values['paciente_especifico'] ? '' : 'hidden'; ?>><label for="cemed-doencas-outro">Descreva outra doença <span aria-hidden="true">*</span></label><input id="cemed-doencas-outro" name="doencas_preexistentes_outro" type="text" maxlength="300" value="<?php echo esc_attr( $values['doencas_preexistentes_outro'] ); ?>"></div>
							<h4>Condições especiais <span aria-hidden="true">*</span></h4>
							<div class="fafar-cemed-field"><div class="fafar-cemed-choices is-stacked" role="group" aria-label="Condições especiais do paciente">
								<?php foreach ( fafar_cemed_patient_special_condition_options() as $condicao ) : ?><label><input type="checkbox" name="condicoes_especiais[]" value="<?php echo esc_attr( $condicao ); ?>" <?php checked( in_array( $condicao, $values['condicoes_especiais'], true ) ); ?>> <?php echo esc_html( $condicao ); ?></label><?php endforeach; ?>
							</div></div>
							<div class="fafar-cemed-field" data-cemed-special-other-wrap <?php echo in_array( 'Outro', $values['condicoes_especiais'], true ) && 'Sim' === $values['paciente_especifico'] ? '' : 'hidden'; ?>><label for="cemed-condicoes-outro">Descreva outra condição especial <span aria-hidden="true">*</span></label><input id="cemed-condicoes-outro" name="condicoes_especiais_outro" type="text" maxlength="300" value="<?php echo esc_attr( $values['condicoes_especiais_outro'] ); ?>"></div>
						</section>
						<div class="fafar-cemed-field"><label for="cemed-informacoes">Outras informações relevantes para o atendimento</label><p class="fafar-cemed-help">Informe, por exemplo, alergias a medicamentos e os medicamentos que o paciente já está usando. Nesse caso, informe a indicação e a posologia de cada medicamento.</p><textarea id="cemed-informacoes" name="informacoes_adicionais" rows="5" maxlength="10000"><?php echo esc_textarea( $values['informacoes_adicionais'] ); ?></textarea></div>
					</fieldset>
					<fieldset>
						<legend>Termo de Consentimento Livre e Esclarecido</legend>
						<div class="fafar-cemed-term">
							<p>O(A) Sr.(a) está sendo convidado(a) como voluntário(a) a participar da pesquisa “A (in)formação como ferramenta para promoção da qualidade e segurança no uso do medicamento”. A pesquisa está sendo desenvolvida pelo Centro de Estudos do Medicamento (Cemed) da Faculdade de Farmácia/UFMG.</p>
							<p>Por favor, leia cuidadosamente as informações a seguir antes de decidir participar ou não desta pesquisa:</p>
							<ol>
								<li><strong>Por que estou sendo convidado a participar dessa pesquisa?</strong><p>Você está sendo convidado a participar da pesquisa porque é um usuário dos serviços do Cemed (solicitante de informação ou ouvinte em seminário), possui vínculo de estágio com o Cemed (como voluntário ou bolsista) ou é um profissional da área da saúde.</p></li>
								<li><strong>Quais são os objetivos da pesquisa?</strong><p>A pesquisa tem três objetivos:</p><ol type="a"><li>avaliar a satisfação dos usuários/participantes das atividades oferecidas pelo Cemed;</li><li>avaliar o impacto na formação e a satisfação com a experiência de ensino-aprendizagem oferecida pelo Cemed;</li><li>avaliar a experiência e o conhecimento de profissionais de saúde sobre a busca, a interpretação e a disseminação de informações científicas nas áreas da saúde e de utilização de medicamentos.</li></ol></li>
								<li><strong>Como será minha participação na pesquisa?</strong><p>Você deverá responder a um questionário on-line, cujas respostas serão posteriormente submetidas à análise e interpretação de dados. Colocamos a seu dispor o projeto de pesquisa para exame detalhado, onde constam dados relativos ao objeto e métodos utilizados.</p></li>
								<li><strong>Quanto tempo irei gastar para responder ao questionário?</strong><p>O questionário vai exigir cerca de 10 minutos do seu tempo.</p></li>
								<li><strong>Minha participação é obrigatória? Receberei algum tipo de benefício pela participação no estudo?</strong><p>Sua participação não é obrigatória, não será remunerada e não trará qualquer outro tipo de benefício pessoal. Os participantes serão beneficiados de forma indireta a partir do conhecimento gerado pelo estudo.</p></li>
								<li><strong>Pode haver algum risco ou constrangimento em participar dessa pesquisa? Meus dados serão divulgados?</strong><p>Para participar dessa pesquisa você terá que fornecer algumas informações pessoais, o que pode gerar algum incômodo ou constrangimento para você. Porém, a equipe de pesquisadores envolvidos garantirá o anonimato de todos os dados informados. Seu nome ou o material que indique sua participação não será divulgado em hipótese alguma e você não será identificado(a) em nenhuma publicação que possa resultar desse estudo. Portanto, os riscos/constrangimentos pessoais são mínimos e não há riscos físicos ou de outra natureza.</p></li>
								<li><strong>E se eu mudar de ideia e não quiser mais participar da pesquisa?</strong><p>Você poderá retirar seu consentimento ou interromper sua participação a qualquer momento. Mesmo que você concorde e responda o questionário, poderá desistir de participar e retirar seu consentimento posteriormente, bem como solicitar que as informações prestadas por você sejam excluídas da pesquisa. Sua recusa não trará nenhum prejuízo em sua relação com o pesquisador ou com o Cemed.</p></li>
								<li><strong>E se eu tiver dúvidas ou comentários sobre o projeto?</strong><p>Você pode entrar em contato, a qualquer momento com qualquer um dos pesquisadores responsáveis pelo projeto no Centro de Estudos do Medicamento (Cemed) pelo telefone (31) 3409-6854 ou pelo e-mail <a href="mailto:cemedufmg@gmail.com">cemedufmg@gmail.com</a>. O Cemed está localizado na sala 3111 do bloco 4 da Faculdade de Farmácia no campus da Universidade Federal de Minas Gerais em Belo Horizonte.</p></li>
							</ol>
							<p><strong>Profª. Coordenadora do Projeto:</strong><br>Cristiane Aparecida Menezes de Pádua<br>Telefone: (31) 3409-6859<br>E-mail: <a href="mailto:cmenezespadua@gmail.com">cmenezespadua@gmail.com</a></p>
							<p>Você poderá contatar o COEP em caso de dúvidas relacionadas aos aspectos éticos.<br>COEP — Comitê de Ética em Pesquisa da Universidade Federal de Minas Gerais<br>Av. Antônio Carlos, 6627, Unidade Administrativa II — 2º andar — Sala 2005<br>Campus Pampulha, Belo Horizonte, MG — Brasil<br>CEP 31270-901<br>Telefax: (31) 3409-4592<br>E-mail: <a href="mailto:coep@prpq.ufmg.br">coep@prpq.ufmg.br</a></p>
							<p>O Centro de Estudos do Medicamento (Cemed/UFMG) compromete-se a conduzir todas as atividades desta pesquisa de acordo com os termos do presente consentimento.</p>
							<p>Projeto (CAAE: 86437017.8.0000.5149) aprovado pelo Comitê de Ética em Pesquisa da UFMG conforme parecer nº 2.615.872.</p>
						</div>
						<div class="fafar-cemed-field"><span class="fafar-cemed-label" id="cemed-consentimento-label">Você concorda em participar da pesquisa? <span aria-hidden="true">*</span></span><p class="fafar-cemed-help">Ao escolher “Sim”, declaro que concordo em participar da pesquisa “A (in)formação como ferramenta para promoção da qualidade e segurança no uso do medicamento”. Declaro que fui informado dos objetivos da pesquisa de maneira clara e detalhada e esclareci minhas dúvidas. Sei que a qualquer momento poderei solicitar novas informações e modificar minha decisão de participar se assim desejar.</p><div class="fafar-cemed-choices" role="group" aria-labelledby="cemed-consentimento-label"><label><input type="radio" name="consentimento" value="Sim" <?php checked( $values['consentimento'], 'Sim' ); ?> required> Sim</label><label><input type="radio" name="consentimento" value="Não" <?php checked( $values['consentimento'], 'Não' ); ?> required> Não</label></div><p class="fafar-cemed-help">A escolha “Não” não impede o envio da sua pergunta ao serviço.</p></div>
					</fieldset>
					<button type="submit">Enviar pergunta</button>
					<p class="fafar-cemed-message is-error" data-cemed-submit-error role="alert" hidden></p>
				</form>
			</div>
		</article>
	<?php endwhile; ?>
</div>

<?php get_footer(); ?>
