<?php get_header(); ?>
<div class="empty-state"><p class="eyebrow">Erro 404</p><h1>Página não encontrada</h1><p>O endereço pode ter mudado. Use a busca ou volte para a página inicial.</p><?php get_search_form(); ?><p><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Ir para a página inicial →</a></p></div>
<?php get_footer(); ?>
