<?php if ( ! fafar_cemed_comments_allowed( get_the_ID() ) || post_password_required() ) { return; } ?>
<section id="comments" class="comments-area" aria-label="Comentários">
<?php if ( have_comments() ) : ?>
<h2>Comentários (<?php echo esc_html( get_comments_number() ); ?>)</h2>
<ol class="comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 40 ) ); ?></ol>
<?php the_comments_pagination( array( 'prev_text' => '← Anteriores', 'next_text' => 'Próximos →' ) ); ?>
<?php endif; ?>
<?php if ( ! comments_open() && get_comments_number() ) : ?><p>Os comentários estão encerrados.</p><?php endif; ?>
<?php comment_form(); ?>
</section>
