<?php
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
	<?php the_content(); ?>
</div>
<?php
endwhile;
get_footer();
