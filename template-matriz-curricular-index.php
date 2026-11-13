<?php
/** Entrada para as matrizes curriculares dos cursos de graduação. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<article class="single-content">
	<header class="entry-header"><h1 class="entry-title">Matriz curricular</h1></header>
	<div class="entry-content">
		<p>Escolha um curso para consultar as disciplinas por período, suas cargas horárias e pré-requisitos.</p>
		<ul>
			<li><a href="<?php echo esc_url( add_query_arg( 'matriz', '1', home_url( '/da-matriz-curricular-far/' ) ) ); ?>">Farmácia — diurno e noturno</a></li>
			<li><a href="<?php echo esc_url( add_query_arg( 'matriz', '1', home_url( '/da-matriz-curricular-bio/' ) ) ); ?>">Biomedicina</a></li>
		</ul>
	</div>
</article>
<?php get_footer();
