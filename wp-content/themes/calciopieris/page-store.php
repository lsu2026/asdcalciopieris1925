<?php
/**
 * Template della pagina "Store" — lo store ufficiale dell'A.S.D. Calcio Pieris 1925.
 *
 * Lo store e' gestito da EYE Sports, che e' anche sponsor del club: per questo motivo
 * il marchio mostrato qui e' lo stesso stemma usato nella pagina Sponsors e nel
 * carosello della home (assets/sponsors/eyestore.jpg). Logo e indirizzo arrivano da
 * cp_store_info() in functions.php, cosi' un aggiornamento fatto in un punto solo si
 * riflette su tutte le pagine che citano lo store.
 *
 * I paragrafi contrassegnati dal badge "Testo provvisorio" contengono descrizioni di
 * servizio non ancora confermate dal club, seguendo la stessa convenzione adottata in
 * page-sponsors.php: vanno sostituiti quando arrivano i testi ufficiali.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$store = function_exists( 'cp_store_info' ) ? cp_store_info() : array(
	'name' => 'EYE Sports',
	'url'  => 'https://www.eyesportshop.com/it/',
	'logo' => get_template_directory_uri() . '/assets/sponsors/eyestore.jpg',
);

/* Maglie della prima squadra. Le foto arrivano dal plugin "Calcio Pieris - Maglie"
   (menu Maglie nella bacheca). Se il plugin non e' attivo si usa l'elenco qui sotto e,
   come ulteriore ripiego, il file omonimo in assets/kit/. Senza immagine la scheda
   mostra un segnaposto. */
$kits = function_exists( 'cp_get_kits' ) ? cp_get_kits() : array(
	array( 'slug' => 'prima',    'name' => 'Prima maglia',    'desc' => 'La maglia di casa, nei colori granata del club.',   'image' => '' ),
	array( 'slug' => 'seconda',  'name' => 'Seconda maglia',  'desc' => 'La maglia utilizzata nelle gare in trasferta.',     'image' => '' ),
	array( 'slug' => 'portiere', 'name' => 'Maglia portiere', 'desc' => 'La maglia di gara del portiere.',                   'image' => '' ),
);

/* Etichette decorative delle schede maglia: numero di riferimento e tipo di gara.
   Stanno qui e non nel plugin perche' sono scelte grafiche del tema. */
$kit_meta = array(
	'prima'    => array( 'num' => '01', 'tag' => 'Casa' ),
	'seconda'  => array( 'num' => '02', 'tag' => 'Trasferta' ),
	'portiere' => array( 'num' => 'GK', 'tag' => 'Portiere' ),
);
?>
<style>
.store-page{--g:var(--granata,#901913);--o:var(--oro,#d6aa63)}
.store-page section{margin:0 0 54px}
.store-hero{display:flex;align-items:center;gap:44px}
.store-hero__logo{flex:0 0 300px;display:flex;justify-content:center;align-items:center;background:#fff;border:1px solid #ececec;border-radius:14px;padding:34px;min-height:200px;box-shadow:0 3px 14px rgba(0,0,0,.07)}
.store-hero__logo img{max-width:100%;max-height:160px;object-fit:contain}
.store-hero__text{flex:1 1 320px}
.store-hero__text h2{color:var(--g);font-size:2rem;margin:0 0 14px;padding-top:0}
.store-lead{color:#444;line-height:1.75;margin:0 0 16px;font-size:1.05rem}
.store-prov{display:inline-block;font-size:.7rem;letter-spacing:1px;text-transform:uppercase;color:#9a7b1f;background:#faf3e2;border:1px solid #ecdfbe;padding:3px 9px;border-radius:5px;margin-bottom:14px}
.store-page h2{color:var(--g)}
.store-kit__head{text-align:center;max-width:640px;margin:0 auto}
.store-kit__head h2{font-size:clamp(1.9rem,3.6vw,2.6rem);text-transform:uppercase;line-height:1.06;margin:0 0 12px;padding-top:0}
.kit-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:32px}
.kit-card{position:relative;background:#fff;border:1px solid #ececec;border-radius:16px;overflow:hidden;box-shadow:0 6px 22px rgba(0,0,0,.07);transition:transform .35s ease,box-shadow .35s ease,border-color .35s ease}
.kit-card:hover{transform:translateY(-8px);box-shadow:0 22px 46px rgba(0,0,0,.16);border-color:var(--o)}
/* barra di accento in cima: tiene il carattere granata ora che il fondo e' bianco */
.kit-card::before{content:"";position:absolute;z-index:3;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--g),var(--o))}
.kit-card__media{position:relative;aspect-ratio:3/4;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#fff;border-bottom:1px solid #eee}
/* trama diagonale appena accennata, in granata tenue */
.kit-card__media::before{content:"";position:absolute;inset:0;background:repeating-linear-gradient(115deg,rgba(144,25,19,.035) 0 2px,transparent 2px 26px);pointer-events:none}
/* contain e non cover: la maglia si vede intera, e il fondo bianco della foto
   si confonde con quello della scheda invece di essere ritagliato */
.kit-card__media img{position:relative;z-index:1;width:100%;height:100%;object-fit:contain;background:#fff;padding:18px;display:block;transition:transform .5s ease}
.kit-card:hover .kit-card__media img{transform:scale(1.04)}
/* numero in filigrana: sta dietro la foto, si vede solo con il segnaposto */
.kit-card__num{position:absolute;z-index:0;left:12px;top:-10px;font-family:var(--font-display,inherit);font-weight:800;font-size:5.4rem;line-height:1;color:transparent;-webkit-text-stroke:2px rgba(214,170,99,.55);pointer-events:none;user-select:none}
.kit-card__tag{position:absolute;z-index:2;right:12px;bottom:12px;border:1px solid rgba(110,18,14,.18);font-family:var(--font-display,inherit);font-weight:700;text-transform:uppercase;letter-spacing:.12em;font-size:.7rem;color:var(--granata-dark,#6e120e);background:var(--o);padding:5px 13px;border-radius:999px;box-shadow:0 4px 12px rgba(0,0,0,.16)}
.kit-ph{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;color:rgba(144,25,19,.34);text-align:center;padding:20px}
.kit-ph span{font-family:var(--font-display,inherit);font-weight:600;font-size:.74rem;letter-spacing:.14em;text-transform:uppercase;line-height:1.4;color:#8d8378;background:#fff;border:1px solid #e4ded6;border-radius:999px;padding:6px 14px}
.kit-card__body{padding:20px 22px 24px}
.kit-card__body h3{color:var(--g);text-transform:uppercase;font-size:1.16rem;margin:0}
.kit-card__body h3::after{content:"";display:block;width:38px;height:3px;background:var(--o);border-radius:2px;margin:9px 0 0}
.kit-card__body p{color:#555;line-height:1.65;margin:11px 0 0;font-size:.92rem}
.store-steps{list-style:none;margin:22px 0 0;padding:0;counter-reset:s}
.store-steps li{counter-increment:s;position:relative;padding:0 0 18px 52px;color:#444;line-height:1.7}
.store-steps li::before{content:counter(s);position:absolute;left:0;top:-2px;width:34px;height:34px;border-radius:50%;background:var(--g);color:#fff;font-family:var(--font-display,inherit);font-weight:700;display:flex;align-items:center;justify-content:center}
.store-cta{text-align:center;background:#faf7f2;border:1px solid #eee;border-left:5px solid var(--o);border-radius:12px;padding:30px 34px}
.store-cta h2{margin:0 0 10px;padding-top:0}
.store-cta p{color:#444;line-height:1.75;margin:0 auto 18px;max-width:620px}
@media(max-width:820px){.kit-grid{grid-template-columns:1fr;max-width:360px;margin-left:auto;margin-right:auto}}
@media(max-width:700px){.store-hero{flex-direction:column;gap:22px}.store-hero__logo{width:100%;flex:1 1 auto;padding:26px;min-height:150px}}
@media(prefers-reduced-motion:reduce){.kit-card,.kit-card__media img{transition:none}.kit-card:hover{transform:none}.kit-card:hover .kit-card__media img{transform:none}}
</style>

<div class="page-hero">
	<div class="container">
		<div class="breadcrumb"><a style="color:var(--oro)" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &rsaquo; Store</div>
		<h1>Store Ufficiale</h1>
	</div>
</div>

<div class="entry-content">
	<div class="container store-page">

		<?php /* Presentazione dello store ufficiale. Il logo e' lo stesso dello sponsor
		   EYE Sports, perche' store e sponsor sono la stessa realta'. */ ?>
		<section class="store-hero">
			<div class="store-hero__logo">
				<a href="<?php echo esc_url( $store['url'] ); ?>" target="_blank" rel="noopener">
					<img src="<?php echo esc_url( $store['logo'] ); ?>" alt="<?php echo esc_attr( $store['name'] ); ?> &mdash; store ufficiale del Calcio Pieris 1925">
				</a>
			</div>
			<div class="store-hero__text">
				<h2><?php echo esc_html( $store['name'] ); ?></h2>
				<p class="store-lead"><?php echo esc_html( $store['name'] ); ?> &egrave; lo store ufficiale dell&rsquo;A.S.D. Calcio Pieris 1925. &Egrave; il punto di riferimento per il materiale tecnico e l&rsquo;abbigliamento del club: le stesse divise e gli stessi capi che la prima squadra e il settore giovanile indossano in campo, in panchina e in trasferta.</p>
				<a class="btn btn-granata" href="<?php echo esc_url( $store['url'] ); ?>" target="_blank" rel="noopener">Vai al kit granata &rarr;</a>
			</div>
		</section>

		<?php /* Da qui in avanti i contenuti sono descrittivi e non ancora confermati dal
		   club: restano marcati come provvisori finche' non arrivano i testi ufficiali. */ ?>
		<?php /* Maglie della prima squadra. Le fotografie non sono ancora disponibili: finche' i
		   file non vengono copiati in assets/kit/ (prima, seconda, portiere - con
		   estensione .webp .jpg .jpeg o .png) ogni scheda mostra un segnaposto, e le
		   immagini compaiono da sole appena i file sono presenti. */ ?>
		<section class="store-kit">
			<div class="store-kit__head">
				<h2>Le maglie della prima squadra</h2>
			</div>
			<div class="kit-grid">
				<?php foreach ( $kits as $kit ) :
					$kit_img = ! empty( $kit['image'] )
						? $kit['image']
						: ( function_exists( 'cp_kit_image_url' ) ? cp_kit_image_url( $kit['slug'] ) : '' );
					$km = isset( $kit_meta[ $kit['slug'] ] ) ? $kit_meta[ $kit['slug'] ] : array( 'num' => '', 'tag' => '' );
					?>
					<article class="kit-card">
						<div class="kit-card__media">
							<?php if ( $km['num'] ) : ?><span class="kit-card__num" aria-hidden="true"><?php echo esc_html( $km['num'] ); ?></span><?php endif; ?>
							<?php if ( $kit_img ) : ?>
								<img src="<?php echo esc_url( $kit_img ); ?>" alt="<?php echo esc_attr( $kit['name'] . ' — Calcio Pieris 1925' ); ?>" loading="lazy">
							<?php else : ?>
								<div class="kit-ph">
									<svg width="62" height="62" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"><path d="M8 3 4.6 4.4 2.5 8.2l3.2 1.7V21h12.6V9.9l3.2-1.7-2.1-3.8L16 3a4 4 0 0 1-8 0Z"/></svg>
									<span>Foto in arrivo</span>
								</div>
							<?php endif; ?>
							<?php if ( $km['tag'] ) : ?><span class="kit-card__tag"><?php echo esc_html( $km['tag'] ); ?></span><?php endif; ?>
						</div>
						<div class="kit-card__body">
							<h3><?php echo esc_html( $kit['name'] ); ?></h3>
							<p><?php echo esc_html( $kit['desc'] ); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="store-how">
			<span class="store-prov">Testo provvisorio</span>
			<h2>Come acquistare</h2>
			<ol class="store-steps">
				<li>Visita lo shop online di <?php echo esc_html( $store['name'] ); ?> e scegli i capi ufficiali del Calcio Pieris 1925.</li>
				<li>Segnala di essere tesserato o genitore di un tesserato del club: lo store riserva condizioni dedicate alle nostre squadre.</li>
				<li>Per ordini di gruppo, personalizzazioni con numero e nome o forniture per le squadre, contatta la segreteria del club.</li>
			</ol>
		</section>

		<div class="store-cta">
			<h2>Hai bisogno di informazioni?</h2>
			<p>Per ordini di squadra, taglie, personalizzazioni o consegne puoi scrivere alla segreteria: ti mettiamo in contatto con lo store.</p>
			<a class="btn btn-granata" href="<?php echo esc_url( home_url( '/contatti/' ) ); ?>">Contattaci</a>
		</div>

	</div>
</div>

<?php get_footer();
