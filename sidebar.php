<aside id="secondary" class="site-sidebar" aria-label="Navegação desta seção">
	<details class="section-navigation" open>
		<summary>Navegue nesta seção</summary>
		<nav aria-label="Menu da seção"><?php if ( is_active_sidebar( 'sidebar-1' ) ) { dynamic_sidebar( 'sidebar-1' ); } else { echo site_fafar_custom_sidebar_menu(); } ?></nav>
	</details>
</aside>
