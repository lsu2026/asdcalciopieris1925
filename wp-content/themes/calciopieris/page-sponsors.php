<?php
/**
 * Template della pagina "Sponsors" — un blocco per sponsor con immagine e descrizione
 * affiancate, alternando i lati (testo/immagine) ad ogni riga.
 * Testi provvisori (lorem ipsum) in attesa delle informazioni ufficiali.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$dir = get_template_directory_uri() . '/assets/sponsors/';
$sponsors = function_exists( 'cp_get_sponsors' ) ? cp_get_sponsors() : array(
	array( 'name' => 'Medicenter',         'url' => 'https://medicentercliniche.it/',   'logo' => $dir . 'medicenter.webp' ),
	array( 'name' => 'Conit',              'url' => 'https://www.conit.org/it/',        'logo' => $dir . 'conit.svg' ),
	array( 'name' => 'G.S.A. Interiors',   'url' => '',                                 'logo' => '' ),
	array( 'name' => 'Ottica Russi',       'url' => 'https://otticarussi.it/',          'logo' => $dir . 'ottica-russi.svg' ),
	array( 'name' => 'H2O Termotecnica',   'url' => 'https://h2otermotecnica.it/',      'logo' => $dir . 'h2o.png' ),
	array( 'name' => 'BCC Venezia Giulia', 'url' => 'https://www.bccveneziagiulia.it/', 'logo' => $dir . 'bcc.webp' ),
	array( 'name' => 'Eye Store',          'url' => 'https://www.eyesportshop.com/it/', 'logo' => $dir . 'eyestore.jpg' ),
);
$lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.';
?>
<style>
.sponsors-page{--g:var(--granata,#901913);--o:var(--oro,#d6aa63)}
.sponsors-intro{max-width:760px;margin:0 auto 48px;text-align:center;color:#555;line-height:1.7}
.sp-row{display:flex;align-items:center;gap:44px;margin:0 0 52px}
.sp-row--rev{flex-direction:row-reverse}
.sp-row__text{flex:1 1 320px}
.sp-row__text h2{color:var(--g);font-size:1.7rem;margin:0 0 14px}
.sp-row__text h2 a{color:inherit;text-decoration:none}
.sp-row__text p{color:#444;line-height:1.75;margin:0 0 18px}
.sp-badge{display:inline-block;font-size:.7rem;letter-spacing:1px;text-transform:uppercase;color:#9a7b1f;background:#faf3e2;border:1px solid #ecdfbe;padding:3px 9px;border-radius:5px;margin-bottom:12px}
.sp-link{display:inline-block;color:var(--g);font-weight:600;text-decoration:none;border-bottom:2px solid var(--o);padding-bottom:2px}
.sp-row__img{flex:1 1 300px;display:flex;justify-content:center;align-items:center;background:#fff;border:1px solid #ececec;border-radius:14px;padding:34px;min-height:190px;box-shadow:0 3px 14px rgba(0,0,0,.07)}
.sp-row__img img{max-width:100%;max-height:150px;object-fit:contain}
.sp-row__ph{font-family:var(--font-display,inherit);font-size:1.6rem;color:var(--g);font-weight:700;text-align:center;letter-spacing:.5px}
.sp-become{text-align:center;max-width:840px;margin:0 auto 46px;background:#faf7f2;border:1px solid #eee;border-left:5px solid var(--o);border-radius:12px;padding:30px 34px}
.sp-become h2{color:var(--g);margin:0 0 10px}
.sp-become p{color:#444;line-height:1.75;margin:0 auto 18px;max-width:640px}
@media(max-width:640px){.sp-row,.sp-row--rev{flex-direction:column;gap:20px;margin-bottom:40px}.sp-row__img{width:100%;padding:26px;min-height:150px}}
</style>

<div class="page-hero">
	<div class="container">
		<div class="breadcrumb"><a style="color:var(--oro)" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &rsaquo; Sponsors</div>
		<h1>I nostri Sponsor</h1>
	</div>
</div>

<div class="entry-content">
	<div class="container sponsors-page">
		<div class="sp-become">
			<h2>Diventa sponsor</h2>
			<p>Sostenere il Calcio Pieris significa investire nello sport del territorio e nei suoi giovani. Contattaci per conoscere le opportunit&agrave; di partnership e visibilit&agrave;.</p>
			<a class="btn btn-granata" href="<?php echo esc_url( home_url( '/contatti/' ) ); ?>">Contattaci</a>
		</div>

		<p class="sponsors-intro">Un grazie di cuore alle aziende che sostengono l&rsquo;A.S.D. Calcio Pieris 1925. Il loro supporto rende possibile la nostra attivit&agrave; sportiva, dal settore giovanile alla prima squadra.</p>

		<?php foreach ( $sponsors as $i => $sp ) :
			$rev  = ( $i % 2 === 1 ) ? ' sp-row--rev' : '';
			$name = esc_html( $sp['name'] );
			$img  = ! empty( $sp['logo'] )
				? '<img src="' . esc_url( $sp['logo'] ) . '" alt="' . esc_attr( $sp['name'] ) . '" loading="lazy">'
				: '<span class="sp-row__ph">' . $name . '</span>';
			?>
			<div class="sp-row<?php echo $rev; ?>">
				<div class="sp-row__text">
					<span class="sp-badge">Testo provvisorio</span>
					<h2><?php if ( $sp['url'] ) : ?><a href="<?php echo esc_url( $sp['url'] ); ?>" target="_blank" rel="noopener"><?php echo $name; ?></a><?php else : echo $name; endif; ?></h2>
					<p><?php echo esc_html( $lorem ); ?></p>
					<?php if ( $sp['url'] ) : ?>
						<a class="sp-link" href="<?php echo esc_url( $sp['url'] ); ?>" target="_blank" rel="noopener">Visita il sito &rarr;</a>
					<?php endif; ?>
				</div>
				<div class="sp-row__img"><?php echo $img; ?></div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<?php get_footer();
