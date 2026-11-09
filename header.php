<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'ffds-root fafar-site' ); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content">Pular para o conteúdo</a>
<header class="site-header">
	<div class="institution-bar"><div class="site-container institution-inner"><a href="https://ufmg.br/">Universidade Federal de Minas Gerais</a><span>Campus Pampulha · Belo Horizonte</span></div></div>
	<div class="holiday-banner" role="note" aria-label="Mensagem de boas festas">
		<span class="holiday-banner__lights" aria-hidden="true"></span>
		<p><span aria-hidden="true">✦</span> Boas festas da Faculdade de Farmácia da UFMG <span aria-hidden="true">✦</span></p>
	</div>
	<div class="site-container masthead">
		<div class="site-branding">
			<?php fafar_site_logo(); ?>
			<div class="site-brand-copy"><p class="brand-kicker">Universidade Federal de Minas Gerais</p><a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a><?php if ( get_bloginfo( 'description' ) ) : ?><p class="site-description"><?php bloginfo( 'description' ); ?></p><?php endif; ?></div>
		</div>
		<div class="header-tools"><span class="header-tools-label">Encontre no site</span><?php get_search_form(); ?></div>
	</div>
	<nav class="main-navigation" aria-label="Navegação principal"><div class="site-container">
		<button class="nav-toggle ffds-btn" type="button" aria-expanded="false" aria-controls="primary-menu" hidden><?php echo fafar_icon( 'menu' ); ?> Menu principal</button>
		<?php echo fafar_menu( array( 'theme_location' => 'primary', 'menu_id' => 'primary-menu', 'menu_class' => 'primary-menu' ) ); ?>
	</div></nav>
</header>
<?php if ( ! is_home() && ! is_search() && ! is_singular() ) { get_custom_banner(); } ?>
<div class="site-container content-layout">
<?php if ( ! is_home() ) { get_sidebar(); } ?>
<main id="main-content" class="main-content" tabindex="-1">
