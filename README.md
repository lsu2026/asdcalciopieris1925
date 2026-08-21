# A.S.D. Calcio Pieris 1925 — sito

Codice del sito della società, in produzione su <https://provi.ct.ws>.

Il repository versiona **solo il codice scritto per questo sito**: il tema e i
plugin sviluppati su misura. Il core di WordPress, i plugin di terze parti, gli
allegati e i file di configurazione restano fuori — se ne occupa WordPress, e
tenerli fuori evita di pubblicare per sbaglio credenziali o dati.

## Cosa c'è dentro

```
wp-content/themes/calciopieris/     tema del sito
wp-content/plugins/calciopieris-*   cinque plugin su misura
```

### Il tema

Tema classico (non a blocchi), granata e oro, tipografia Barlow Condensed.
Oltre ai template standard ha pagine dedicate: `page-store.php`,
`page-squadre.php`, `page-prima-squadra.php`, `page-settore-giovanile.php`,
`page-organigramma.php`, `page-sponsors.php`, `page-contatti.php`.

`assets/reveal.js` gestisce la comparsa degli elementi allo scorrimento. È
scritto **senza IntersectionObserver** di proposito: la soglia percentuale
dell'observer è una frazione dell'area dell'elemento, quindi su una sezione più
alta dello schermo può essere irraggiungibile e il contenuto resterebbe
invisibile per sempre. Il commento in testa al file spiega il caso reale.

### I plugin

| Plugin | Cosa fa |
|---|---|
| `calciopieris-prima-squadra.php` | Stagioni, calendario, classifica. Shortcode `[pieris_prima_squadra]`. Il carosello carica solo la stagione corrente e chiede le altre via AJAX al click. |
| `calciopieris-sponsors.php` | Elenco sponsor con logo, per il carosello in home e la pagina Sponsors. |
| `calciopieris-maglie` (file `calciopieris-divise.php`) | Foto delle tre maglie della prima squadra, mostrate nella pagina Store. |
| `calciopieris-organigramma.php` | Organigramma societario. |
| `calciopieris-staff.php` | Staff tecnico per prima squadra e settore giovanile. |
| `calciopieris-news-url.php` | News come embed di post Facebook, tramite l'iframe ufficiale. |

## Percorsi delle immagini

Le immagini gestite dai plugin sono salvate nel database come **percorso
relativo a `wp-content`** (es. `themes/calciopieris/assets/sponsors/eyestore.jpg`),
non come URL assoluto. L'indirizzo completo viene ricostruito a ogni richiesta
da `cp_asset_url()` in `functions.php`, che segue sempre il dominio in uso.

Non è un dettaglio stilistico: con gli URL assoluti le immagini sparivano
aprendo il sito da un indirizzo diverso da quello usato al salvataggio — per
esempio dal cellulare via IP di rete locale, o dopo un cambio di dominio.

## Ambiente locale

Installazione WordPress sotto XAMPP in `C:\xampp\htdocs\pieris`, database
MySQL locale. Il `wp-config.php` locale ricava `WP_HOME` e `WP_SITEURL`
dall'host della richiesta, così il sito risponde sia da `localhost` sia
dall'indirizzo di rete locale (utile per provare dal telefono).

`wp-config.php` non è versionato: contiene le credenziali del database e
differisce fra locale e produzione.

## Rilasci

In produzione si carica **solo il delta** rispetto al rilascio precedente, mai
l'intero sito. Quando cambiano `style.css` o i file in `assets/`, va alzata la
**versione del tema** nell'intestazione di `style.css`: gli asset sono serviti
con `?ver=<versione>` e senza il cambio i browser continuano a usare le copie
in cache.
