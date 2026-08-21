<?php
/**
 * Template della pagina "Organigramma".
 * Il contenuto viene generato dal plugin "Calcio Pieris – Organigramma"
 * tramite lo shortcode [pieris_organigramma], così l'organigramma è
 * modificabile da WP-Admin senza toccare il contenuto della pagina.
 * Se il plugin non è attivo, ricade sul contenuto della pagina.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
while ( have_posts() ) : the_post();
?>
<div class="page-hero">
	<div class="container">
		<div class="breadcrumb"><a style="color:var(--oro)" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &rsaquo; <?php the_title(); ?></div>
		<h1><?php the_title(); ?></h1>
	</div>
</div>
<div class="entry-content">
	<?php
	if ( shortcode_exists( 'pieris_organigramma' ) ) {
		echo do_shortcode( '[pieris_organigramma]' );
	} else {
		the_content();
	}
	?>
</div>
<?php
endwhile;
get_footer();
