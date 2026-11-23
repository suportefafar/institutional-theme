<?php
/** Consulta das atividades com período sugerido nas versões curriculares. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$data = json_decode( file_get_contents( get_stylesheet_directory() . '/data/matrizes-curriculares.json' ), true );
$bibliography = json_decode( file_get_contents( get_stylesheet_directory() . '/data/bibliografia-curricular.json' ), true );
$bio = is_page( 'da-matriz-curricular-bio' );
$versions = $bio ? array( '20262' => 'N-20262', '20189' => 'N-20189', '20181' => 'N-20181', '20162' => 'N-20162', '20131' => 'N-20131', '20101X' => 'N-20101X' ) : array( '2025' => '2025.2', '2018' => '2018.1', '2016' => '2016.1X' );
$bibliography_version = $bio ? '20262' : '2025';
// Nome, versão oficial, documento, identificador da seleção, turno e representação curricular.
$sets = $bio ? array(
	'biomedicina' => array( 'Biomedicina · Noturno', 'N-20262', 'https://www.farmacia.ufmg.br/wp-content/uploads/2026/04/N-20262.pdf', '20262', 'biomedicina' ),
	'biomedicina-20189' => array( 'Biomedicina · Noturno', 'N-20189', 'https://www.farmacia.ufmg.br/wp-content/uploads/2024/11/Matriz-Curricular-BiomedicinaN-20189.pdf', '20189', 'biomedicina' ),
	'biomedicina-20181' => array( 'Biomedicina · Noturno', 'N-20181', 'https://www.farmacia.ufmg.br/wp-content/uploads/2024/11/MatrizN-20181.pdf', '20181', 'biomedicina' ),
	'biomedicina-20162' => array( 'Biomedicina · Noturno', 'N-20162', 'https://www.farmacia.ufmg.br/wp-content/uploads/2024/11/MatrizN-20162.pdf', '20162', 'biomedicina' ),
	'biomedicina-20131' => array( 'Biomedicina · Noturno', 'N-20131', 'https://www.farmacia.ufmg.br/wp-content/uploads/2024/11/MatrizN-20131.pdf', '20131', 'biomedicina' ),
	'biomedicina-20101X' => array( 'Biomedicina · Noturno', 'N-20101X', 'https://www.farmacia.ufmg.br/wp-content/uploads/2024/11/MatrizN-20101X.pdf', '20101X', 'biomedicina' ),
) : array(
	'farmacia-diurno' => array( 'Farmácia · Diurno', 'D-20252', 'https://www.farmacia.ufmg.br/wp-content/uploads/2025/08/Farmacia-2025-2-Diurno.pdf', '2025', 'farmacia-diurno' ),
	'farmacia-noturno' => array( 'Farmácia · Noturno', 'N-20252', 'https://www.farmacia.ufmg.br/wp-content/uploads/2025/08/Farmacia-2025-2-Noturno.pdf', '2025', 'farmacia-noturno' ),
	'farmacia-diurno-2018' => array( 'Farmácia · Diurno', 'D-20181', 'https://www.farmacia.ufmg.br/wp-content/uploads/2019/05/FARM%C3%81CIA-D20181.pdf', '2018', 'farmacia-diurno', 'https://www.farmacia.ufmg.br/wp-content/uploads/2018/07/Representa%C3%A7%C3%A3o-Curricular-2018-1-DIURNO.pdf' ),
	'farmacia-noturno-2018' => array( 'Farmácia · Noturno', 'N-20181', 'https://www.farmacia.ufmg.br/wp-content/uploads/2019/05/FARM%C3%81CIA-N20181.pdf', '2018', 'farmacia-noturno', 'https://www.farmacia.ufmg.br/wp-content/uploads/2018/07/Representa%C3%A7%C3%A3o-Curricular-2018-1-NOTURNO.pdf' ),
	'farmacia-diurno-2016' => array( 'Farmácia · Diurno', 'D-20161X', 'https://www.farmacia.ufmg.br/wp-content/uploads/2016/04/D20161X-ATUAL.pdf', '2016', 'farmacia-diurno', 'https://www.farmacia.ufmg.br/wp-content/uploads/2018/07/Repres.-Curr.-Diurno-20161X.pdf' ),
	'farmacia-noturno-2016' => array( 'Farmácia · Noturno', 'N-20161X', 'https://www.farmacia.ufmg.br/wp-content/uploads/2016/04/N20161X-ATUAL.pdf', '2016', 'farmacia-noturno', 'https://www.farmacia.ufmg.br/wp-content/uploads/2018/07/Repres.-Curr.-Noturno-20161X.pdf' ),
);
$max_period = $bio ? 11 : 13;
get_header();
?>
<article class="curriculum <?php echo $bio ? 'curriculum--biomedicina' : 'curriculum--farmacia'; ?>">
	<a class="curriculum-back" href="<?php echo esc_url( get_permalink() ); ?>">← Voltar aos documentos do curso</a>
	<header><p class="curriculum-kicker">Graduação · <?php echo $bio ? 'Biomedicina' : 'Farmácia'; ?></p><h1>Matriz curricular</h1><p>Consulte as atividades por período sugerido, carga horária e pré-requisitos.</p></header>
	<p class="curriculum-note"><?php echo $bio ? 'Consulte as versões atuais e históricas disponibilizadas pelo Colegiado de Biomedicina. A versão N-20262 é aplicada aos calouros de 2026.2 e está prevista para todos os estudantes a partir de 2027.1, em substituição à N-20189. Selecione a versão correspondente ao seu vínculo curricular.' : 'Matrizes de 2025, 2018 e 2016 disponibilizadas pelo Colegiado de Farmácia, para os turnos diurno e noturno. Selecione a versão correspondente ao seu vínculo curricular. Algumas atividades dependem do percurso escolhido.'; ?> As matrizes incluem atividades optativas e outras cargas horárias sem disciplina definida que não aparecem nesta lista. Consulte o documento oficial para os percursos e todas as regras de integralização.</p>
	<p class="curriculum-bibliography-intro" data-version="<?php echo esc_attr( $bibliography_version ); ?>">Quando disponível, a bibliografia básica e complementar pode ser consultada junto a cada disciplina. As referências foram extraídas do <a href="<?php echo esc_url( $bio ? 'https://www.farmacia.ufmg.br/wp-content/uploads/2026/05/Projeto_pedagogico_assinado.pdf' : 'https://www.farmacia.ufmg.br/wp-content/uploads/2026/06/PPC_Farmacia-incluidono-SEI.pdf' ); ?>">projeto pedagógico oficial</a>; consulte o plano de ensino para indicações atualizadas.</p>
	<?php foreach ( $versions as $year => $label ) : ?>
	<div class="curriculum-sources" data-version="<?php echo esc_attr( $year ); ?>"><strong>Documentos oficiais · <?php echo esc_html( $label ); ?>:</strong><?php foreach ( $sets as $set ) : if ( (string) $year !== $set[3] ) { continue; } ?><a href="<?php echo esc_url( $set[2] ); ?>"><?php echo esc_html( $set[0] . ' (' . $set[1] . ')' ); ?></a><?php if ( isset( $set[5] ) ) : ?><a href="<?php echo esc_url( $set[5] ); ?>"><?php echo esc_html( 'Representação curricular · ' . $set[0] ); ?></a><?php endif; ?><?php endforeach; ?></div>
	<?php endforeach; ?>
	<?php if ( ! is_array( $data ) ) : ?><p>Não foi possível carregar a matriz. Consulte os documentos oficiais acima.</p><?php else : ?>
	<form class="curriculum-filters" role="search" onsubmit="return false">
		<div><label for="curriculum-search">Pesquisar disciplina ou código</label><input id="curriculum-search" type="search" placeholder="Ex.: anatomia ou MOF009"></div>
		<div><label for="curriculum-version">Versão curricular</label><select id="curriculum-version"><?php foreach ( $versions as $year => $label ) : ?><option value="<?php echo esc_attr( $year ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></div>
		<?php if ( ! $bio ) : ?><div><label for="curriculum-course">Turno</label><select id="curriculum-course"><option value="farmacia-diurno">Diurno</option><option value="farmacia-noturno">Noturno</option></select></div><?php endif; ?>
		<div><label for="curriculum-period">Período</label><select id="curriculum-period"><option value="">Todos os períodos</option><?php for ( $i = 1; $i <= $max_period; $i++ ) : ?><option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?>º período</option><?php endfor; ?></select></div>
		<button type="reset">Limpar filtros</button>
	</form>
	<section class="curriculum-dependencies" aria-labelledby="dependencies-title" hidden>
		<div><p class="curriculum-kicker">Explore o percurso</p><h2 id="dependencies-title">Dependências entre disciplinas</h2><p>Escolha uma atividade para visualizar seus pré-requisitos e as atividades que a exigem.</p></div>
		<label for="curriculum-dependency">Disciplina ou atividade</label><select id="curriculum-dependency"></select>
		<div class="curriculum-graph" aria-live="polite"></div>
		<p class="curriculum-graph-note">O quadro mostra relações diretas entre atividades com período sugerido. Outros requisitos e regras constam no documento oficial.</p>
	</section>
	<p class="curriculum-status" aria-live="polite"></p>
	<?php foreach ( $sets as $key => $set ) : ?>
	<section class="curriculum-set" data-course="<?php echo esc_attr( $set[4] ); ?>" data-version="<?php echo esc_attr( $set[3] ); ?>"><h2><?php echo esc_html( $set[0] ); ?> <small><?php echo esc_html( $set[1] ); ?></small></h2>
		<?php $last = 0; foreach ( $data[ $key ] as $entry ) : if ( $last !== $entry['periodo'] ) : if ( $last ) : ?></tbody></table></div></section><?php endif; $last = $entry['periodo']; ?>
		<section class="curriculum-period" data-period="<?php echo esc_attr( $last ); ?>"><h3><?php echo esc_html( $last ); ?>º período</h3><div class="curriculum-scroll" role="region" aria-label="<?php echo esc_attr( $last . 'º período' ); ?>" tabindex="0"><table><thead><tr><th scope="col">Disciplina / atividade</th><th scope="col">Carga horária</th><th scope="col">Pré-requisitos</th></tr></thead><tbody>
		<?php endif; $references = ( $bibliography_version === $set[3] ) ? ( $bibliography[ $bio ? 'biomedicina' : 'farmacia' ][ $entry['codigo'] ] ?? null ) : null; ?><tr class="curriculum-row" data-code="<?php echo esc_attr( $entry['codigo'] ); ?>" data-name="<?php echo esc_attr( $entry['nome'] ); ?>" data-requisites="<?php echo esc_attr( $entry['requisitos'] ); ?>"><td><strong><?php echo esc_html( $entry['nome'] ); ?></strong><span class="curriculum-code"><?php echo esc_html( $entry['codigo'] ); ?></span><?php if ( $references ) : ?><details class="curriculum-bibliography"><summary>Bibliografia recomendada</summary><?php foreach ( array( 'basica' => 'Básica', 'complementar' => 'Complementar' ) as $reference_type => $label ) : if ( empty( $references[ $reference_type ] ) ) { continue; } ?><h4><?php echo esc_html( $label ); ?></h4><ul><?php foreach ( $references[ $reference_type ] as $reference ) : ?><li><?php echo esc_html( $reference ); ?></li><?php endforeach; ?></ul><?php endforeach; ?></details><?php endif; ?></td><td><?php echo esc_html( $entry['total'] ); ?> h <span class="curriculum-detail">Teórica: <?php echo esc_html( $entry['teorica'] ); ?> h · Prática: <?php echo esc_html( $entry['pratica'] ); ?> h</span></td><td><?php echo esc_html( '-' === $entry['requisitos'] ? 'Não informado no quadro' : $entry['requisitos'] ); ?></td></tr>
		<?php endforeach; if ( $last ) : ?></tbody></table></div></section><?php endif; ?>
	</section><?php endforeach; ?>
	<p class="curriculum-empty" hidden>Nenhuma atividade encontrada para os filtros selecionados.</p>
	<?php endif; ?>
</article>
<?php get_footer();
