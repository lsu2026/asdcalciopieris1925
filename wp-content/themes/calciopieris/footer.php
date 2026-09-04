<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<footer class="site-footer">
	<div class="footer-top">
		<div class="container">
			<div class="footer-brand">
				<h4>A.S.D. Calcio Pieris 1925</h4>
				<p>A.S.D. Calcio Pieris 1925. Cent&rsquo;anni di calcio, famiglia e territorio nel cuore della Bisiacaria. Rispetto, squadra, impegno e crescita.</p>
			</div>
			<div>
				<h4>Il Club</h4>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/la-nostra-storia/' ) ); ?>">La nostra storia</a></li>
					<li><a href="<?php echo esc_url( home_url( '/organigramma/' ) ); ?>">Organigramma</a></li>
					<li><a href="<?php echo esc_url( home_url( '/prima-squadra/' ) ); ?>">Prima squadra</a></li>
				</ul>
			</div>
			<div>
				<h4>Contatti</h4>
				<ul>
					<li>Via Anna Frank 3<br>Pieris &mdash; San Canzian d&rsquo;Isonzo (GO)</li>
					<li>Tel. <a href="tel:+393346684760">+39 334 6684760</a></li>
					<li><a href="mailto:asdcalciopieris1925@gmail.com">asdcalciopieris1925@gmail.com</a></li>
				</ul>
			</div>
			<div>
				<h4>Seguici</h4>
				<ul>
					<li><a href="https://www.facebook.com/calciopieris/" target="_blank" rel="noopener">Facebook</a></li>
					<li><a href="https://www.instagram.com/calciopieris1925/" target="_blank" rel="noopener">Instagram</a></li>
				</ul>
			</div>
		</div>
	</div>
	<div class="footer-bottom">
		<div class="container">
			<span>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> A.S.D. Calcio Pieris 1925 &mdash; Tutti i diritti riservati</span>
			<span>Matricola CONI 95780 &middot; F.I.G.C. &ndash; L.N.D. Friuli Venezia Giulia</span>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
