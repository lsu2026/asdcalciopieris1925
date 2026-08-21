<?php
/**
 * Template della pagina "Contatti".
 * Le sezioni "Gioca con noi" e "Diventa sponsor" sono state spostate rispettivamente
 * nelle pagine Squadre e Sponsors, quindi vengono rimosse dal contenuto qui.
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
	$cp = apply_filters( 'the_content', get_the_content() );
	// Rimuove tutto dalla sezione "Gioca con noi" in poi (include anche "Diventa sponsor").
	$cp = preg_replace( '#<h2[^>]*>\s*Gioca con noi\s*</h2>.*$#is', '', $cp );
	echo $cp;
	?>
</div>
<?php
endwhile;
get_footer();
