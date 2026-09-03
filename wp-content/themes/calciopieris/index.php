<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<div class="page-hero">
	<div class="container">
		<div class="breadcrumb"><a style="color:var(--oro)" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &rsaquo; News</div>
		<h1><?php echo is_home() ? 'News' : esc_html( get_the_archive_title() ); ?></h1>
	</div>
</div>
<div class="archive-grid">
	<div class="container">
		<?php if ( have_posts() ) : ?>
		<div class="news-embeds-home news-embeds-list">
			<?php while ( have_posts() ) : the_post();
				$cp_content  = get_the_content();
				$cp_is_embed = ( '1' === get_post_meta( get_the_ID(), '_cpemb', true ) ) || has_shortcode( $cp_content, 'pieris_fb_embed' );
				if ( $cp_is_embed ) :
			?>
			<div class="news-embed-item"><?php echo do_shortcode( $cp_content ); ?></div>
			<?php else : ?>
			<article class="card news-card">
				<?php if ( has_post_thumbnail() ) : ?>
				<a class="news-thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium_large' ); ?></a>
				<?php endif; ?>
				<div class="news-body">
					<div class="news-meta"><?php echo esc_html( get_the_date() ); ?></div>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
					<a class="leggi" href="<?php the_permalink(); ?>">Leggi tutto &rarr;</a>
				</div>
			</article>
			<?php endif; endwhile; ?>
		</div>
		<div class="pagination"><?php echo paginate_links(); ?></div>
		<?php endif; ?>

		<?php
		/* Sotto le news scritte a mano, quelle che arrivano da sole dalla
		   Pagina Facebook. Restano separate e non mescolate: le prime portano
		   foto e reazioni, queste solo il testo, e fingere che siano la stessa
		   cosa si vedrebbe. Il titolo dice da dove vengono. */
		$cp_feed_news = function_exists( 'cp_feed_facebook' ) ? cp_feed_facebook( 'Pieris - News (tutti)' ) : '';
		if ( $cp_feed_news ) :
		?>
		<section class="news-da-facebook">
			<div class="section-head">
				<div class="overline">Aggiornato automaticamente</div>
				<h2>Dalla nostra pagina Facebook</h2>
			</div>
			<?php echo $cp_feed_news; // gia' ripulito dal plugin ?>
		</section>
		<?php elseif ( ! have_posts() ) : ?>
		<p style="text-align:center;">Nessun articolo trovato.</p>
		<?php endif; ?>
	</div>
</div>
<?php get_footer(); ?>
