</main>
</div>
<?php
$services = site_fafar_highlight_area();
if ( $services ) { echo '<div class="site-container services-area">' . $services . '</div>'; }
$locations = get_nav_menu_locations();
$footer_links = array();
foreach ( array( 'primary', 'secondary_menu' ) as $location ) {
	foreach ( wp_get_nav_menu_items( $locations[ $location ] ?? 0 ) ?: array() as $item ) {
		if ( $item->url && '#' !== $item->url ) { $footer_links[ wp_strip_all_tags( $item->title ) ] = $item->url; }
	}
}
$footer_links['Intranet'] = $footer_links['Intranet'] ?? 'https://intranet.farmacia.ufmg.br/';
$footer_groups = array(
	'A Faculdade' => array( 'História' => 'História', 'Administração' => 'Administração', 'Corpo Docente' => 'Corpo docente', 'CENEX' => 'Extensão · CENEX', 'NAPQ' => 'Pesquisa · NAPQ', 'Eventos' => 'Eventos' ),
	'Ensino e recursos' => array( 'O Curso' => 'Farmácia', 'Biomedicina' => 'Biomedicina', 'Biblioteca' => 'Biblioteca', 'Editais' => 'Editais', 'Mapa de Salas' => 'Mapa de salas', 'Ementas - Validar' => 'Validar ementas', 'Intranet' => 'Intranet' ),
);
?>
<footer class="site-footer">
	<div class="site-container footer-main">
		<div class="footer-navigation">
		<?php foreach ( $footer_groups as $heading => $items ) : ?>
			<nav class="footer-group" aria-label="<?php echo esc_attr( $heading ); ?>">
				<h2><?php echo esc_html( $heading ); ?></h2>
				<ul><?php foreach ( $items as $key => $label ) : if ( ! isset( $footer_links[ $key ] ) ) { continue; } ?>
					<li><a href="<?php echo esc_url( $footer_links[ $key ] ); ?>"><?php echo esc_html( $label ); ?></a></li>
				<?php endforeach; ?></ul>
			</nav>
		<?php endforeach; ?>
		</div>
		<a class="footer-feature" href="<?php echo esc_url( $footer_links['Institucional'] ?? home_url( '/' ) ); ?>">
			<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/fafar-vista-aerea.jpeg' ); ?>" width="600" height="600" alt="Vista aérea da Faculdade de Farmácia da UFMG" loading="lazy">
			<span>Conheça a FAFAR <?php echo fafar_icon( 'arrow-right' ); ?></span>
		</a>
	</div>
	<div class="site-container footer-identity">
		<p><strong><?php bloginfo( 'name' ); ?></strong> <span>Universidade Federal de Minas Gerais</span></p>
		<address>Av. Antônio Carlos, 6627 · Campus UFMG – Pampulha<br>Belo Horizonte · MG · Brasil · CEP 31270-901</address>
	</div>
	<div class="site-container footer-bottom">
		<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?> · UFMG</p>
		<div class="footer-signature"><img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/logo-ufmg.png' ); ?>" width="100" height="28" alt="UFMG" loading="lazy"><a href="#main-content" class="footer-back">Voltar ao conteúdo <span aria-hidden="true">↑</span></a></div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
