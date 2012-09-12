<?php
class Football_Pool_Admin_Options extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		$date = date_i18n( 'Y-m-d H:i' );
		
		$options = array(
						//array( 'text', __( 'Verwijder data bij deïnstallatie', FOOTBALLPOOL_TEXT_DOMAIN ), 'remove_data_on_uninstall', __( '', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Webmaster', FOOTBALLPOOL_TEXT_DOMAIN ), 'webmaster', __( 'Deze waarde wordt gebruikt voor de shortcode [webmaster].', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Inleg', FOOTBALLPOOL_TEXT_DOMAIN ), 'money', __( 'Als je voor geld speelt, dan is dit het bedrag dat spelers moeten betalen. De shortcode [money] geeft deze waarde weer in de content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Bank', FOOTBALLPOOL_TEXT_DOMAIN ), 'bank', __( 'Als je voor geld speelt, dan is dit de persoon waar het geld moet worden betaald. De shortcode [bank] geeft deze waarde weer in de content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Startdatum', FOOTBALLPOOL_TEXT_DOMAIN ), 'start', __( 'De startdatum van het toernooi. De shortcode [start] geeft deze waarde weer in de content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Volle Score *', FOOTBALLPOOL_TEXT_DOMAIN ), 'fullpoints', __( 'Aantal punten dat een speler krijgt wanneer de exacte uitkomst van een wedstrijd is voorspeld. De shortcode [fullpoints] geeft deze waarde weer in de content. De waarde wordt ook gebruikt in de berekeningen voor het totaal aantal punten van een speler.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Toto Score *', FOOTBALLPOOL_TEXT_DOMAIN ), 'totopoints', __( 'Aantal punten dat een speler krijgt wanneer de uitkomst van een wedstrijd (winnaar, verliezer, gelijkspel) goed is voorspeld, zonder de exacte uitkomst goed te hebben. De shortcode [totopoints] geeft deze waarde weer in de content. De waarde wordt ook gebruikt in de berekeningen voor het totaal aantal punten van een speler.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Voorspellingsstop (in seconden) *', FOOTBALLPOOL_TEXT_DOMAIN ), 'maxperiod', __( 'Een speler kan zijn/haar voorspellingen aanpassen tot aan deze tijd vóór de start van een wedstrijd. De tijd is in seconden, bv. vul voor 15 minuten de waarde 900 in.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'datetime', __( 'Eén stoptijd *', FOOTBALLPOOL_TEXT_DOMAIN ), 'force_locktime', __( 'Wanneer hier een geldige datum en tijd wordt ingevuld [Y-m-d H:s], dan is de tijd waarvoor een wedstrijdvoorspelling of bonusvraag moet zijn ingevoerd, vastgezet op deze datum en tijd en is deze niet meer dynamisch afhankelijk van de starttijd van een wedstrijd of de tijd die bij een bonusvraag is ingesteld. (lokale tijd:', FOOTBALLPOOL_TEXT_DOMAIN ) . ' <a href="options-general.php">' . $date . '</a>)' ),
						array( 'text', __( 'Maximum lengte shoutboxbericht *', FOOTBALLPOOL_TEXT_DOMAIN ), 'shoutbox_max_chars', __( 'De maximale lengte (aantal karakters) van een bericht in de shoutbox.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'checkbox', __( 'Gebruik Pools', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_leagues', __( 'Stel in of de plugin Pools moet gebruiken om de spelers in te delen. Gebruik bv. voor betalende spelers en niet betalende spelers, of voor verschillende afdelingen. Let op: als je deze waarde wijzigt en er zijn al punten toegekend, dan wordt de standentabel niet automatisch bijgewerkt met de (eventueel) gewijzigde spelers in de pool. Gebruik hiervoor de knop op deze pagina.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Standaardpool voor nieuwe gebruikers', FOOTBALLPOOL_TEXT_DOMAIN ), 'default_league_new_user', __( 'De standaardpool (<a href="?page=footballpool-leagues">ID van de pool</a>) waar nieuwe spelers in worden geplaatst nadat ze zich hebben geregistreerd.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Afbeelding voor Dashboard Widget', FOOTBALLPOOL_TEXT_DOMAIN ), 'dashboard_image', '<a href="' . get_admin_url() . '">Dashboard</a>' ),
						array( 'checkbox', __( 'Admin Bar verbergen voor subscribers', FOOTBALLPOOL_TEXT_DOMAIN ), 'hide_admin_bar', __( 'Subscribers kunnen na inloggen de WordPress Admin Bar bovenin het scherm krijgen (instelbaar per user). Hier kan je instellen of de plugin deze waarde moet negeren en de Admin Bar altijd moet weglaten.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'checkbox', __( 'Favicon gebruiken', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_favicon', __( 'Zet uit om niet de icons van de plugin te gebruiken.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'checkbox', __( 'Apple touch icon gebruiken', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_touchicon', __( 'Zet uit om niet de icons van de plugin te gebruiken.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'checkbox', __( 'Use charts', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_charts', sprintf( __( 'Om charts te kunnen gebruiken, moet de <%s>Highcharts API zijn geïnstalleerd<%s>.', FOOTBALLPOOL_TEXT_DOMAIN ), 'a href="?page=footballpool-help#charts"', '/a' ) ),
					);
		
		self::admin_header( __( 'Plugin Instelllingen', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		if ( Football_Pool_Utils::post_string( 'recalculate' ) == 'Recalculate Scores' ) {
			self::update_score_history();
			self::notice( __( 'Scores zijn opnieuw berekend.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		} elseif ( Football_Pool_Utils::post_string( 'form_action' ) == 'update' ) {
			foreach ( $options as $option ) {
				if ( $option[0] == 'text' ) {
					$value = Football_Pool_Utils::post_string( $option[2] );
				} elseif ( $value[0] == 'date' || $option[0] == 'datetime' ) {
					$y = Football_Pool_Utils::post_integer( $option[2] . '_y' );
					$m = Football_Pool_Utils::post_integer( $option[2] . '_m' );
					$d = Football_Pool_Utils::post_integer( $option[2] . '_d' );
					$value = ( $y != 0 && $m != 0 && $d != 0 ) ? sprintf( '%04d-%02d-%02d', $y, $m, $d ) : '';
					
					if ( $value != '' && $option[0] == 'datetime' ) {
						$h = Football_Pool_Utils::post_integer( $option[2] . '_h', -1 );
						$i = Football_Pool_Utils::post_integer( $option[2] . '_i', -1 );
						$value = ( $h != -1 && $i != -1 ) ? sprintf( '%s %02d:%02d', $value, $h, $i ) : '';
					}
				} else {
					$value = Football_Pool_Utils::post_integer( $option[2] );
				}
				
				self::set_value( $option[2], $value );
			}
			self::notice( __( 'Wijzigingen opgeslagen.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
		
		$chart = new Football_Pool_Chart;
		if ( $chart->stats_enabled && ! $chart->API_loaded ) {
			self::notice( __( 'Charts are enabled but Highcharts API was not found!', FOOTBALLPOOL_TEXT_DOMAIN ) , 'important' );
		}
		
		self::intro( __( 'Als waarden in de velden die gemarkeerd zijn met een asterisk, worden leeggelaten, dan zal de plugin terugvallen op de waarden zoals die bij installatie van de plugin zijn ingesteld.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		self::options_form( $options );
		
		submit_button( null, 'primary', null, false );
		submit_button( 'Recalculate Scores', 'secondary', 'recalculate', false );
		
		self::admin_footer();
	}
}
?>