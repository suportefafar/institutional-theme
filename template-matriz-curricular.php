<?php
/** Consulta das atividades com período sugerido nas versões curriculares. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$data = json_decode( file_get_contents( get_stylesheet_directory() . '/data/matrizes-curriculares.json' ), true );
$bibliography = json_decode( file_get_contents( get_stylesheet_directory() . '/data/bibliografia-curricular.json' ), true );
$bio = is_page( 'da-matriz-curricular-bio' );
$sets = $bio ? array( 'biomedicina' => array( 'Biomedicina · Noturno', 'N-20262', 'https://www.farmacia.ufmg.br/wp-content/uploads/2026/04/N-20262.pdf' ) ) : array(
	'farmacia-diurno' => array( 'Farmácia · Diurno', 'D-20252', 'https://www.farmacia.ufmg.br/wp-content/uploads/2025/08/Farmacia-2025-2-Diurno.pdf' ),
	'farmacia-noturno' => array( 'Farmácia · Noturno', 'N-20252', 'https://www.farmacia.ufmg.br/wp-content/uploads/2025/08/Farmacia-2025-2-Noturno.pdf' ),
);
get_header();
?>
<article class="curriculum <?php echo $bio ? 'curriculum--biomedicina' : 'curriculum--farmacia'; ?>">
	<a class="curriculum-back" href="<?php echo esc_url( get_permalink() ); ?>">← Voltar aos documentos do curso</a>
	<header><p class="curriculum-kicker">Graduação · <?php echo $bio ? 'Biomedicina' : 'Farmácia'; ?></p><h1>Matriz curricular</h1><p>Consulte as atividades por período sugerido, carga horária e pré-requisitos.</p></header>
	<p class="curriculum-note"><?php echo $bio ? 'Versão N-20262, prevista para todos os estudantes a partir de 2027.1.' : 'Versões D-20252 e N-20252 disponibilizadas pelo Colegiado de Farmácia. A página oficial não identifica uma versão específica de 2027.'; ?> As matrizes incluem atividades optativas e outras cargas horárias sem disciplina definida que não aparecem nesta lista. Consulte o documento oficial para os percursos e todas as regras de integralização.</p>
	<p class="curriculum-bibliography-intro">Quando disponível, a bibliografia básica e complementar pode ser consultada junto a cada disciplina. As referências foram extraídas do <a href="<?php echo esc_url( $bio ? 'https://www.farmacia.ufmg.br/wp-content/uploads/2026/05/Projeto_pedagogico_assinado.pdf' : 'https://www.farmacia.ufmg.br/wp-content/uploads/2026/06/PPC_Farmacia-incluidono-SEI.pdf' ); ?>">projeto pedagógico oficial</a>; consulte o plano de ensino para indicações atualizadas.</p>
	<div class="curriculum-sources">Documentos oficiais: <?php foreach ( $sets as $set ) : ?><a href="<?php echo esc_url( $set[2] ); ?>"><?php echo esc_html( $set[0] . ' (' . $set[1] . ')' ); ?> ↗</a><?php endforeach; ?></div>
	<?php if ( ! is_array( $data ) ) : ?><p>Não foi possível carregar a matriz. Consulte os documentos oficiais acima.</p><?php else : ?>
	<section class="curriculum-dependencies" aria-labelledby="dependencies-title" hidden>
		<div><p class="curriculum-kicker">Explore o percurso</p><h2 id="dependencies-title">Dependências entre disciplinas</h2><p>Escolha uma atividade para visualizar seus pré-requisitos e as atividades que a exigem.</p></div>
		<label for="curriculum-dependency">Disciplina ou atividade</label><select id="curriculum-dependency"></select>
		<div class="curriculum-graph" aria-live="polite"></div>
		<p class="curriculum-graph-note">O quadro mostra relações diretas entre atividades com período sugerido. Outros requisitos e regras constam no documento oficial.</p>
	</section>
	<form class="curriculum-filters" role="search" onsubmit="return false">
		<div><label for="curriculum-search">Pesquisar disciplina ou código</label><input id="curriculum-search" type="search" placeholder="Ex.: anatomia ou MOF009"></div>
		<?php if ( ! $bio ) : ?><div><label for="curriculum-course">Turno</label><select id="curriculum-course"><option value="farmacia-diurno">Diurno</option><option value="farmacia-noturno">Noturno</option></select></div><?php endif; ?>
		<div><label for="curriculum-period">Período</label><select id="curriculum-period"><option value="">Todos os períodos</option><?php for ( $i = 1; $i <= ( $bio ? 10 : 12 ); $i++ ) : ?><option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?>º período</option><?php endfor; ?></select></div>
		<button type="reset">Limpar filtros</button>
	</form>
	<p class="curriculum-status" aria-live="polite"></p>
	<?php foreach ( $sets as $key => $set ) : ?>
	<section class="curriculum-set" data-course="<?php echo esc_attr( $key ); ?>"><h2><?php echo esc_html( $set[0] ); ?> <small><?php echo esc_html( $set[1] ); ?></small></h2>
		<?php $last = 0; foreach ( $data[ $key ] as $entry ) : if ( $last !== $entry['periodo'] ) : if ( $last ) : ?></tbody></table></div></section><?php endif; $last = $entry['periodo']; ?>
		<section class="curriculum-period" data-period="<?php echo esc_attr( $last ); ?>"><h3><?php echo esc_html( $last ); ?>º período</h3><div class="curriculum-scroll" role="region" aria-label="<?php echo esc_attr( $last . 'º período' ); ?>" tabindex="0"><table><thead><tr><th scope="col">Disciplina / atividade</th><th scope="col">Carga horária</th><th scope="col">Pré-requisitos</th></tr></thead><tbody>
		<?php endif; $references = $bibliography[ $bio ? 'biomedicina' : 'farmacia' ][ $entry['codigo'] ] ?? null; ?><tr class="curriculum-row" data-code="<?php echo esc_attr( $entry['codigo'] ); ?>" data-name="<?php echo esc_attr( $entry['nome'] ); ?>" data-requisites="<?php echo esc_attr( $entry['requisitos'] ); ?>"><td><strong><?php echo esc_html( $entry['nome'] ); ?></strong><span class="curriculum-code"><?php echo esc_html( $entry['codigo'] ); ?></span><?php if ( $references ) : ?><details class="curriculum-bibliography"><summary>Bibliografia recomendada</summary><?php foreach ( array( 'basica' => 'Básica', 'complementar' => 'Complementar' ) as $reference_type => $label ) : if ( empty( $references[ $reference_type ] ) ) { continue; } ?><h4><?php echo esc_html( $label ); ?></h4><ul><?php foreach ( $references[ $reference_type ] as $reference ) : ?><li><?php echo esc_html( $reference ); ?></li><?php endforeach; ?></ul><?php endforeach; ?></details><?php endif; ?></td><td><?php echo esc_html( $entry['total'] ); ?> h <span class="curriculum-detail">Teórica: <?php echo esc_html( $entry['teorica'] ); ?> h · Prática: <?php echo esc_html( $entry['pratica'] ); ?> h</span></td><td><?php echo esc_html( '-' === $entry['requisitos'] ? 'Não informado no quadro' : $entry['requisitos'] ); ?></td></tr>
		<?php endforeach; if ( $last ) : ?></tbody></table></div></section><?php endif; ?>
	</section><?php endforeach; ?>
	<p class="curriculum-empty" hidden>Nenhuma atividade encontrada para os filtros selecionados.</p>
	<?php endif; ?>
</article>
<?php get_footer();
