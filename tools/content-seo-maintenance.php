<?php
/**
 * Revisão conservadora de conteúdo publicado.
 *
 * Uso no servidor:
 * php tools/content-seo-maintenance.php /var/www/html [--apply]
 *
 * O script preserva post_modified/post_modified_gmt e não cria revisões.
 */

if ( PHP_SAPI !== 'cli' ) { exit( 1 ); }

$root  = $argv[1] ?? '/var/www/html';
$apply = in_array( '--apply', $argv, true );
require rtrim( $root, '/' ) . '/wp-load.php';

global $wpdb;

function fafar_seo_plain_text( $value ) {
	$value = strip_shortcodes( (string) $value );
	$value = html_entity_decode( wp_strip_all_tags( $value, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$value = preg_replace( '~https?://\S+~u', '', $value );
	return trim( preg_replace( '/\s+/u', ' ', $value ) );
}

function fafar_seo_excerpt( $post ) {
	$text = fafar_seo_plain_text( $post->post_excerpt ?: $post->post_content );
	if ( mb_strlen( $text ) < 45 ) {
		$text = trim( $post->post_title, " \t\n\r\0\x0B.\"" ) . '. Informações publicadas pela Faculdade de Farmácia da UFMG.';
	}
	if ( mb_strlen( $text ) <= 158 ) { return $text; }
	$cut = mb_substr( $text, 0, 157 );
	$space = mb_strrpos( $cut, ' ' );
	if ( false !== $space && $space > 120 ) { $cut = mb_substr( $cut, 0, $space ); }
	return rtrim( $cut, " ,;:." ) . '…';
}

function fafar_seo_suspicion( $post ) {
	$title   = remove_accents( mb_strtolower( fafar_seo_plain_text( $post->post_title ) ) );
	$content = remove_accents( mb_strtolower( fafar_seo_plain_text( $post->post_content ) ) );
	if ( str_contains( $title, 'resultado preliminar' ) && preg_match( '/prazo.{0,100}resultado final/u', $content ) ) {
		return 'O prazo de recurso do resultado preliminar parece ser contado a partir do resultado final.';
	}
	if ( preg_match_all( '/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/isu', $post->post_content, $links, PREG_SET_ORDER ) ) {
		foreach ( $links as $link ) {
			$href  = remove_accents( mb_strtolower( rawurldecode( $link[1] ) ) );
			$label = remove_accents( mb_strtolower( fafar_seo_plain_text( $link[2] ) ) );
			if ( str_contains( $href, 'resultado-final' ) && str_contains( $label, 'resultado preliminar' ) ) {
				return 'O link rotulado como resultado preliminar aponta para um arquivo chamado resultado final.';
			}
			if ( str_contains( $href, 'resultado-preliminar' ) && str_contains( $label, 'resultado final' ) ) {
				return 'O link rotulado como resultado final aponta para um arquivo chamado resultado preliminar.';
			}
		}
	}
	return '';
}

function fafar_seo_rewrite_text( $content, &$used_rules ) {
	if ( preg_match( '/<(?:pre|code|script|style|textarea)\b/i', $content ) ) { return $content; }
	$rules = array(
		'/\bInformamos que está disponível\b/u'       => 'Está disponível',
		'/\bInformamos que estão disponíveis\b/u'    => 'Estão disponíveis',
		'/\bOs interessados deverão consultar\b/u'   => 'Os interessados devem consultar',
		'/\bOs candidatos deverão consultar\b/u'     => 'Os candidatos devem consultar',
		'/\bOs candidatos poderão consultar\b/u'     => 'Os candidatos podem consultar',
		'/\bdeverá ser preenchido\b/u'                => 'deve ser preenchido',
		'/\bdeverá ser preenchida\b/u'                => 'deve ser preenchida',
		'/\batravés do link\b/ui'                     => 'pelo link',
		'/\batravés do e-mail\b/ui'                   => 'por e-mail',
		'/\batravés do email\b/ui'                    => 'por e-mail',
		'/\bemail\b/ui'                               => 'e-mail',
		'/\bA coordenação do programa torna público o Resultado\b/u' => 'A coordenação do programa divulga o resultado',
	);
	$parts = preg_split( '/(<[^>]+>)/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $parts as $index => $part ) {
		if ( isset( $part[0] ) && '<' === $part[0] ) { continue; }
		foreach ( $rules as $pattern => $replacement ) {
			$count = 0;
			$part = preg_replace( $pattern, $replacement, $part, -1, $count );
			if ( $count ) { $used_rules[ $pattern ] = ( $used_rules[ $pattern ] ?? 0 ) + $count; }
		}
		$parts[ $index ] = $part;
	}
	return implode( '', $parts );
}

function fafar_seo_title( $title ) {
	$title = trim( preg_replace( '/\s+/u', ' ', $title ) );
	$title = preg_replace( '/\s+-\s+/u', ' – ', $title );
	return preg_replace( '/\b(20\d{2})\s+([12])\b/u', '$1/$2', $title );
}

function fafar_seo_generic_image_title( $title ) {
	return (bool) preg_match( '/^(?:img|image|dsc|foto|captura|whatsapp|screen[\s_-]?shot)[\s0-9a-z._-]*$/iu', trim( $title ) );
}

function fafar_seo_logo_alt( $title ) {
	if ( preg_match( '/^(?:logo|logotipo|logomarca)[\s_-]+(.+)$/iu', $title, $match ) ) {
		return 'Logotipo de ' . trim( $match[1] );
	}
	if ( preg_match( '/^(.+?)[\s_-]+(?:logo|logotipo|logomarca)(?:[\s_-]+(.+))?$/iu', $title, $match ) ) {
		$alt = 'Logotipo de ' . trim( $match[1] );
		if ( ! empty( $match[2] ) ) { $alt .= ', versão ' . mb_strtolower( trim( $match[2] ) ); }
		return $alt;
	}
	return '';
}

function fafar_seo_alt( $attachment_id, $post_title ) {
	$attachment = $attachment_id ? get_post( $attachment_id ) : null;
	if ( $attachment ) {
		$current = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		if ( $current ) { return $current; }
		$caption = fafar_seo_plain_text( $attachment->post_excerpt );
		if ( $caption ) { return wp_html_excerpt( $caption, 160, '…' ); }
		$title = fafar_seo_plain_text( preg_replace( '/\.(?:jpe?g|png|gif|webp|svg)$/i', '', $attachment->post_title ) );
		$title = trim( preg_replace( '/[_-]+/u', ' ', $title ) );
		if ( $title && ! fafar_seo_generic_image_title( $title ) ) {
			$logo_alt = fafar_seo_logo_alt( $title );
			if ( $logo_alt ) { return $logo_alt; }
			return wp_html_excerpt( $title, 160, '…' );
		}
	}
	return wp_html_excerpt( 'Imagem relacionada a ' . fafar_seo_plain_text( $post_title ), 160, '…' );
}

function fafar_seo_attachment_from_src( $src ) {
	$id = (int) attachment_url_to_postid( $src );
	if ( $id ) { return $id; }
	$clean = preg_replace( '/-\d+x\d+(?=\.[a-z0-9]+(?:\?|$))/iu', '', $src );
	$id = (int) attachment_url_to_postid( $clean );
	if ( $id ) { return $id; }
	return 0;
}

function fafar_seo_images( $content, $post_title, &$attachment_alts ) {
	return preg_replace_callback( '/<img\b[^>]*>/iu', function ( $match ) use ( $post_title, &$attachment_alts ) {
		$tag = $match[0];
		if ( preg_match( '/\balt\s*=\s*(["\'])(.*?)\1/isu', $tag, $alt_match ) && trim( wp_strip_all_tags( $alt_match[2] ) ) ) { return $tag; }
		$attachment_id = 0;
		if ( preg_match( '/\bwp-image-(\d+)\b/i', $tag, $id_match ) ) { $attachment_id = (int) $id_match[1]; }
		if ( ! $attachment_id && preg_match( '/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $tag, $src_match ) ) {
			$attachment_id = fafar_seo_attachment_from_src( $src_match[1] );
		}
		$alt = fafar_seo_alt( $attachment_id, $post_title );
		if ( $attachment_id ) { $attachment_alts[ $attachment_id ] = $alt; }
		$escaped = esc_attr( $alt );
		if ( preg_match( '/\balt\s*=\s*(["\'])(.*?)\1/isu', $tag ) ) {
			return preg_replace( '/\balt\s*=\s*(["\'])(.*?)\1/isu', 'alt="' . $escaped . '"', $tag, 1 );
		}
		return preg_replace( '/\s*\/?>$/u', ' alt="' . $escaped . '"$0', $tag, 1 );
	}, $content );
}

$report = array(
	'generated_at' => gmdate( 'c' ),
	'mode' => $apply ? 'apply' : 'dry-run',
	'counts' => array( 'examined' => 0, 'content_changed' => 0, 'title_changed' => 0, 'descriptions' => 0, 'inline_alts' => 0, 'attachment_alts' => 0, 'improved_attachment_alts' => 0, 'skipped' => 0, 'skipped_images' => 0 ),
	'changed' => array(),
	'skipped' => array(),
	'skipped_images' => array(),
	'rules' => array(),
);

$posts = get_posts( array( 'post_type' => array( 'post', 'page' ), 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
$attachment_alts = array();
foreach ( $posts as $post ) {
	$report['counts']['examined']++;
	$reason = fafar_seo_suspicion( $post );
	if ( $reason ) {
		$report['counts']['skipped']++;
		$report['skipped'][] = array( 'id' => $post->ID, 'url' => get_permalink( $post ), 'reason' => $reason );
		continue;
	}
	$description = fafar_seo_excerpt( $post );
	$old_description = (string) get_post_meta( $post->ID, '_fafar_meta_description', true );
	$new_title = fafar_seo_title( $post->post_title );
	$new_content = fafar_seo_rewrite_text( $post->post_content, $report['rules'] );
	$new_content = fafar_seo_images( $new_content, $new_title, $attachment_alts );
	if ( preg_replace( '/<img\b[^>]*>/iu', '<img>', $new_content ) !== preg_replace( '/<img\b[^>]*>/iu', '<img>', fafar_seo_rewrite_text( $post->post_content, $discard ) ) ) {
		$report['counts']['skipped']++;
		$report['skipped'][] = array( 'id' => $post->ID, 'url' => get_permalink( $post ), 'reason' => 'A validação estrutural do HTML não foi conclusiva.' );
		continue;
	}
	$content_changed = $new_content !== $post->post_content;
	$title_changed = $new_title !== $post->post_title;
	$description_changed = $description !== $old_description;
	if ( $content_changed ) { $report['counts']['content_changed']++; }
	if ( $title_changed ) { $report['counts']['title_changed']++; }
	if ( $description_changed ) { $report['counts']['descriptions']++; }
	if ( $content_changed || $title_changed || $description_changed ) {
		$report['changed'][] = array( 'id' => $post->ID, 'url' => get_permalink( $post ), 'content' => $content_changed, 'title' => $title_changed, 'description' => $description_changed, 'modified' => $post->post_modified, 'modified_gmt' => $post->post_modified_gmt );
	}
	if ( $apply ) {
		$updates = array();
		$formats = array();
		if ( $content_changed ) { $updates['post_content'] = $new_content; $formats[] = '%s'; }
		if ( $title_changed ) { $updates['post_title'] = $new_title; $formats[] = '%s'; }
		if ( $updates ) {
			$result = $wpdb->update( $wpdb->posts, $updates, array( 'ID' => $post->ID ), $formats, array( '%d' ) );
			if ( false === $result ) { throw new RuntimeException( "Falha ao atualizar {$post->ID}: {$wpdb->last_error}" ); }
			clean_post_cache( $post->ID );
		}
		if ( $description_changed ) { update_post_meta( $post->ID, '_fafar_meta_description', $description ); }
	}
}

foreach ( $attachment_alts as $attachment_id => $alt ) {
	if ( '' === trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ) {
		$report['counts']['attachment_alts']++;
		if ( $apply ) { update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt ); }
	}
}
$report['counts']['inline_alts'] = count( $attachment_alts );

// Substitui nomes de arquivo e nomes crus de logomarca gerados na etapa anterior.
$image_alts = $wpdb->get_results(
	"SELECT p.ID, p.post_title, p.post_parent, m.meta_value alt
	 FROM {$wpdb->posts} p
	 INNER JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key='_wp_attachment_image_alt'
	 WHERE p.post_type='attachment' AND p.post_mime_type LIKE 'image/%' AND TRIM(m.meta_value)<>''"
);
foreach ( $image_alts as $image ) {
	$title = trim( preg_replace( '/[_-]+/u', ' ', preg_replace( '/\.(?:jpe?g|png|gif|webp|svg)$/i', '', $image->post_title ) ) );
	$current = trim( $image->alt );
	$replacement = '';
	if ( $image->post_parent && fafar_seo_generic_image_title( $current ) ) {
		$replacement = 'Imagem relacionada a ' . get_the_title( $image->post_parent );
	} elseif ( fafar_seo_logo_alt( $title ) && remove_accents( mb_strtolower( preg_replace( '/\s+/u', ' ', $current ) ) ) === remove_accents( mb_strtolower( preg_replace( '/\s+/u', ' ', $title ) ) ) ) {
		$replacement = fafar_seo_logo_alt( $title );
	}
	if ( $replacement && $replacement !== $current ) {
		$report['counts']['improved_attachment_alts']++;
		if ( $apply ) { update_post_meta( (int) $image->ID, '_wp_attachment_image_alt', $replacement ); }
	}
}

// Cobre imagens usadas como miniaturas, galerias e campos personalizados.
$missing_images = $wpdb->get_results(
	"SELECT p.ID, p.post_title, p.post_parent
	 FROM {$wpdb->posts} p
	 LEFT JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key='_wp_attachment_image_alt'
	 WHERE p.post_type='attachment' AND p.post_mime_type LIKE 'image/%'
	 AND (m.meta_value IS NULL OR TRIM(m.meta_value)='')"
);
foreach ( $missing_images as $image ) {
	$title = trim( preg_replace( '/\.(?:jpe?g|png|gif|webp|svg)$/i', '', $image->post_title ) );
	if ( ! $image->post_parent && fafar_seo_generic_image_title( $title ) ) {
		$report['counts']['skipped_images']++;
		$report['skipped_images'][] = array( 'id' => (int) $image->ID, 'reason' => 'Nome de arquivo genérico e nenhuma página de origem para descrever a imagem.' );
		continue;
	}
	$context = $image->post_parent ? get_the_title( $image->post_parent ) : 'Faculdade de Farmácia da UFMG';
	$alt = fafar_seo_alt( (int) $image->ID, $context );
	$report['counts']['attachment_alts']++;
	if ( $apply ) { update_post_meta( (int) $image->ID, '_wp_attachment_image_alt', $alt ); }
}

$report_file = '/tmp/fafar-content-seo-report-' . ( $apply ? 'apply' : 'dry-run' ) . '.json';
file_put_contents( $report_file, wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
echo wp_json_encode( $report['counts'], JSON_UNESCAPED_UNICODE ), "\nreport=$report_file\n";
