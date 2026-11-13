<?php
/** Guia de primeiros passos para estudantes da Faculdade de Farmácia. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( isset( $_GET['procedimentos'] ) && '1' === $_GET['procedimentos'] ) {
	require __DIR__ . '/template-procedimentos-estudantes.php';
	return;
}

get_header();
$home = untrailingslashit( home_url( '/' ) );
?>
<article class="student-guide" aria-labelledby="student-guide-title">
	<section class="student-guide-hero">
		<p class="student-guide-kicker">Faculdade de Farmácia · UFMG</p>
		<h1 id="student-guide-title">Comece por aqui</h1>
		<p class="student-guide-lead">Bem-vinda, bem-vinde e bem-vindo à FAFAR! Reunimos neste espaço os serviços, orientações e lugares que ajudam você a viver a Universidade desde o primeiro dia.</p>
		<a class="student-guide-hero-link" href="#primeiros-passos">Encontre o que você precisa <span aria-hidden="true">↓</span></a>
		<div class="student-guide-orbit" aria-hidden="true"><span>FAFAR</span><i></i></div>
	</section>

	<nav class="student-guide-index" aria-label="Nesta página">
		<a href="#primeiros-passos">Primeiros passos</a>
		<a href="#vida-academica">Vida acadêmica</a>
		<a href="#apoio-e-servicos">Apoio e serviços</a>
		<a href="#campus-e-comunidade">Campus e comunidade</a>
	</nav>

	<section class="student-guide-section" id="primeiros-passos" aria-labelledby="student-guide-first-title">
		<div class="student-guide-section-heading"><span class="student-guide-number">01</span><div><p class="student-guide-overline">Seu acesso à Universidade</p><h2 id="student-guide-first-title">Primeiros passos</h2></div></div>
		<div class="student-guide-grid student-guide-grid--four">
			<a class="student-guide-card student-guide-card--feature" href="https://sistemas.ufmg.br/" target="_blank" rel="noopener noreferrer"><span class="student-guide-card-icon" aria-hidden="true">01</span><span class="student-guide-card-content"><strong>Minha UFMG</strong><span>Acesse seu e-mail institucional, o SiGA e os ambientes virtuais da Universidade.</span></span><span class="student-guide-arrow" aria-hidden="true">↗</span></a>
			<a class="student-guide-card" href="https://www.ufmg.br/viverufmg/primeiros-passos/carteira-da-ufmg/" target="_blank" rel="noopener noreferrer"><span class="student-guide-card-icon" aria-hidden="true">02</span><span class="student-guide-card-content"><strong>Carteira da UFMG</strong><span>Veja como solicitar sua identificação universitária para circular e acessar serviços.</span></span><span class="student-guide-arrow" aria-hidden="true">↗</span></a>
			<a class="student-guide-card" href="https://www.redesemfio.ufmg.br/" target="_blank" rel="noopener noreferrer"><span class="student-guide-card-icon" aria-hidden="true">03</span><span class="student-guide-card-content"><strong>Wi-Fi eduroam</strong><span>Configure a rede sem fio da UFMG nos seus dispositivos.</span></span><span class="student-guide-arrow" aria-hidden="true">↗</span></a>
			<a class="student-guide-card" href="https://www.fump.ufmg.br/" target="_blank" rel="noopener noreferrer"><span class="student-guide-card-icon" aria-hidden="true">04</span><span class="student-guide-card-content"><strong>Assistência estudantil</strong><span>Conheça os programas e benefícios oferecidos pela Fump.</span></span><span class="student-guide-arrow" aria-hidden="true">↗</span></a>
		</div>
	</section>

	<section class="student-guide-section" id="vida-academica" aria-labelledby="student-guide-academic-title">
		<div class="student-guide-section-heading"><span class="student-guide-number">02</span><div><p class="student-guide-overline">Organize seu percurso</p><h2 id="student-guide-academic-title">Vida acadêmica</h2></div></div>
		<div class="student-guide-grid">
			<a class="student-guide-card student-guide-card--biomedicina" href="<?php echo esc_url( $home . '/matricula/' ); ?>"><span class="student-guide-card-icon" aria-hidden="true">↗</span><span class="student-guide-card-content"><strong>Matrícula · Biomedicina</strong><span>Consulte orientações, etapas e cronogramas de matrícula.</span></span><span class="student-guide-arrow" aria-hidden="true">→</span></a>
			<a class="student-guide-card student-guide-card--farmacia" href="<?php echo esc_url( $home . '/calendario-escolar-1/' ); ?>"><span class="student-guide-card-icon" aria-hidden="true">↗</span><span class="student-guide-card-content"><strong>Matrícula · Farmácia</strong><span>Consulte os prazos de matrícula no calendário do curso.</span></span><span class="student-guide-arrow" aria-hidden="true">→</span></a>
			<a class="student-guide-card student-guide-card--biomedicina" href="<?php echo esc_url( $home . '/calendario/' ); ?>"><span class="student-guide-card-icon" aria-hidden="true">▦</span><span class="student-guide-card-content"><strong>Calendário acadêmico · Biomedicina</strong><span>Acompanhe as datas importantes do curso.</span></span><span class="student-guide-arrow" aria-hidden="true">→</span></a>
			<a class="student-guide-card student-guide-card--farmacia" href="<?php echo esc_url( $home . '/calendario-escolar-1/' ); ?>"><span class="student-guide-card-icon" aria-hidden="true">▦</span><span class="student-guide-card-content"><strong>Calendário acadêmico · Farmácia</strong><span>Acompanhe as datas importantes do curso.</span></span><span class="student-guide-arrow" aria-hidden="true">→</span></a>
			<a class="student-guide-card" href="https://sistemas.ufmg.br/" target="_blank" rel="noopener noreferrer"><span class="student-guide-card-icon" aria-hidden="true">▤</span><span class="student-guide-card-content"><strong>SiGA e requerimentos</strong><span>Acesse o sistema acadêmico para acompanhar sua vida escolar e fazer solicitações.</span></span><span class="student-guide-arrow" aria-hidden="true">↗</span></a>
		<a class="student-guide-card" href="<?php echo esc_url( add_query_arg( 'procedimentos', '1', $home . '/comece-por-aqui/' ) ); ?>"><span class="student-guide-card-icon" aria-hidden="true">＋</span><span class="student-guide-card-content"><strong>Procedimentos e documentos</strong><span>Encontre orientações sobre trancamento, colação de grau e outros assuntos acadêmicos.</span></span><span class="student-guide-arrow" aria-hidden="true">→</span></a>
		</div>
		<p class="student-guide-note">Precisa consultar horários, currículo ou regras do curso? <a href="<?php echo esc_url( $home . '/farmacia/' ); ?>">Acesse as informações da graduação em Farmácia</a>.</p>
	</section>

	<section class="student-guide-section" id="apoio-e-servicos" aria-labelledby="student-guide-support-title">
		<div class="student-guide-section-heading"><span class="student-guide-number">03</span><div><p class="student-guide-overline">Conte com a gente</p><h2 id="student-guide-support-title">Apoio e serviços</h2></div></div>
		<div class="student-guide-grid">
			<a class="student-guide-card" href="<?php echo esc_url( $home . '/biblioteca-3/' ); ?>"><span class="student-guide-card-icon" aria-hidden="true">⌕</span><span class="student-guide-card-content"><strong>Biblioteca da FAFAR</strong><span>Consulte o acervo, serviços de pesquisa, empréstimos e orientações para trabalhos.</span></span><span class="student-guide-arrow" aria-hidden="true">→</span></a>
			<a class="student-guide-card" href="https://www.fump.ufmg.br/" target="_blank" rel="noopener noreferrer"><span class="student-guide-card-icon" aria-hidden="true">♡</span><span class="student-guide-card-content"><strong>Assistência e permanência</strong><span>Informações sobre alimentação, moradia, saúde e apoio à trajetória universitária.</span></span><span class="student-guide-arrow" aria-hidden="true">↗</span></a>
			<a class="student-guide-card" href="https://www.ufmg.br/vida-academica/diversidade-e-inclusao/acessibilidade-e-inclusao/" target="_blank" rel="noopener noreferrer"><span class="student-guide-card-icon" aria-hidden="true">◎</span><span class="student-guide-card-content"><strong>Acessibilidade e inclusão</strong><span>Conheça o NAI e os recursos de inclusão e acessibilidade da UFMG.</span></span><span class="student-guide-arrow" aria-hidden="true">↗</span></a>
			<a class="student-guide-card" href="https://www.ufmg.br/saudemental/rede-de-apoio/acolhimento/" target="_blank" rel="noopener noreferrer"><span class="student-guide-card-icon" aria-hidden="true">✳</span><span class="student-guide-card-content"><strong>Escuta FAFAR</strong><span>Veja os canais de acolhimento da UFMG e entre em contato com a Assessoria Educacional.</span></span><span class="student-guide-arrow" aria-hidden="true">↗</span></a>
		</div>
	</section>

	<section class="student-guide-section" id="campus-e-comunidade" aria-labelledby="student-guide-campus-title">
		<div class="student-guide-section-heading"><span class="student-guide-number">04</span><div><p class="student-guide-overline">Aprender também é participar</p><h2 id="student-guide-campus-title">Campus e comunidade</h2></div></div>
		<div class="student-guide-grid">
			<a class="student-guide-card" href="https://www.ufmg.br/" target="_blank" rel="noopener noreferrer"><span class="student-guide-card-icon" aria-hidden="true">⌖</span><span class="student-guide-card-content"><strong>Conheça a UFMG</strong><span>Explore os campi, serviços, notícias e informações gerais da Universidade.</span></span><span class="student-guide-arrow" aria-hidden="true">↗</span></a>
			<a class="student-guide-card" href="<?php echo esc_url( $home . '/eventos/' ); ?>"><span class="student-guide-card-icon" aria-hidden="true">✳</span><span class="student-guide-card-content"><strong>Eventos e atividades</strong><span>Acompanhe o que acontece na Faculdade de Farmácia.</span></span><span class="student-guide-arrow" aria-hidden="true">→</span></a>
			<a class="student-guide-card" href="<?php echo esc_url( $home . '/cenex/' ); ?>"><span class="student-guide-card-icon" aria-hidden="true">↗</span><span class="student-guide-card-content"><strong>Extensão e oportunidades</strong><span>Descubra projetos, atividades e formas de participar da vida acadêmica.</span></span><span class="student-guide-arrow" aria-hidden="true">→</span></a>
			<a class="student-guide-card" href="<?php echo esc_url( $home . '/mapa-de-salas/' ); ?>"><span class="student-guide-card-icon" aria-hidden="true">⌖</span><span class="student-guide-card-content"><strong>Encontre sua sala</strong><span>Consulte o mapa de salas e localize os espaços da Faculdade.</span></span><span class="student-guide-arrow" aria-hidden="true">→</span></a>
			<a class="student-guide-card" href="mailto:assessoriaeducacional@farmacia.ufmg.br"><span class="student-guide-card-icon" aria-hidden="true">✉</span><span class="student-guide-card-content"><strong>Fale com a FAFAR</strong><span>Entre em contato com a Assessoria Educacional para orientação e acolhimento.</span></span><span class="student-guide-arrow" aria-hidden="true">↗</span></a>
		</div>
	</section>

	<footer class="student-guide-footer">
		<div><p class="student-guide-overline">Este espaço é seu</p><h2>Sentiu falta de alguma informação?</h2><p>Este guia foi feito para facilitar sua chegada e sua vida na Faculdade de Farmácia. Consulte também o site da UFMG e os canais da graduação para informações atualizadas.</p></div>
		<a href="<?php echo esc_url( $home . '/contatocol/' ); ?>">Fale com o Colegiado <span aria-hidden="true">→</span></a>
	</footer>
</article>
<?php get_footer(); ?>
