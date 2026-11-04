<?php
/** Bootstrap do tema FAFAR Institucional. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

foreach ( array(
    'setup', 'seo', 'helpers', 'template-tags', 'assets', 'shortcodes', 'banner', 'integrations',
    'modules/ppgcf', 'modules/ppgact', 'modules/cemed', 'modules/e-book',
    'modules/cenex-permissions', 'modules/registration-pages', 'dynamic-pages',
) as $module ) {
    require_once __DIR__ . '/inc/' . $module . '.php';
}
