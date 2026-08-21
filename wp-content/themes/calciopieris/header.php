<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="topbar">
	<div class="container">
		<span class="topbar-info">&#9917; A.S.D. Calcio Pieris &mdash; dal 1925 &mdash; Matricola CONI 95780</span>
		<span class="topbar-contatti">
			<span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>351 502 1700</span>
			<a href="mailto:asdcalciopieris1925@gmail.com"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>asdcalciopieris1925@gmail.com</a>
			<a href="https://www.facebook.com/calciopieris/" target="_blank" rel="noopener"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>Facebook</a>
			<a href="https://www.instagram.com/calciopieris1925/" target="_blank" rel="noopener"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>Instagram</a>
		</span>
	</div>
</div>

<header class="site-header">
	<div class="container">
		<a class="site-branding" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<span class="brand-badge"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/scudetto.webp' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></span>
			<span class="site-title">Calcio Pieris 1925<small>Associazione Sportiva Dilettantistica</small></span>
		</a>
		<button class="menu-toggle" aria-label="Apri il menu" onclick="document.querySelector('.main-nav').classList.toggle('open')">&#9776; MENU</button>
		<nav class="main-nav" aria-label="Menu principale">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'fallback_cb'    => 'wp_page_menu',
			) );
			?>
		</nav>
	</div>
</header>
