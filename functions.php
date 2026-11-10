<?php
/** Bootstrap do tema FAFAR Institucional. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

foreach ( array(
    'setup', 'seo', 'helpers', 'template-tags', 'responsive-tables', 'assets', 'shortcodes', 'banner', 'integrations',
    'modules/ppgcf', 'modules/ppgact', 'modules/cemed', 'modules/e-book', 'modules/ementas',
    'modules/cenex-permissions', 'modules/registration-pages', 'modules/auditorium-reservations', 'modules/ppgmaf-registration', 'dynamic-pages',
) as $module ) {
    require_once __DIR__ . '/inc/' . $module . '.php';
}
