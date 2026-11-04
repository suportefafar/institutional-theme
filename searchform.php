<?php $search_id = wp_unique_id( 'site-search-' ); ?>
<form role="search" method="get" class="site-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
<label class="screen-reader-text" for="<?php echo esc_attr( $search_id ); ?>">Buscar no site</label>
<input class="ffds-form-control" type="search" id="<?php echo esc_attr( $search_id ); ?>" name="s" value="<?php echo get_search_query(); ?>" placeholder="O que você procura?">
<button class="ffds-btn ffds-btn-primary" type="submit" aria-label="Buscar no site"><?php echo fafar_icon( 'search' ); ?></button>
</form>
