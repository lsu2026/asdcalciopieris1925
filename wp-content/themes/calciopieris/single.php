<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
while ( have_posts() ) : the_post();
?>
<div class="page-hero">
	<div class="container">
		<div class="breadcrumb"><a style="color:var(--oro)" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &rsaquo; <?php echo esc_html( get_the_date() ); ?></div>
		<h1><?php the_title(); ?></h1>
	</div>
</div>
<div class="entry-content">
	<?php if ( has_post_thumbnail() ) : ?>
		<p><?php the_post_thumbnail( 'large', array( 'style' => 'border-radius:12px;' ) ); ?></p>
	<?php endif; ?>
	<?php the_content(); ?>
	<p style="margin-top:2.5em;"><a class="btn btn-outline" href="<?php echo esc_url( home_url( '/#news' ) ); ?>">&larr; Torna alle news</a></p>
</div>
<?php
endwhile;
get_footer();
