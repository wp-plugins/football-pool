<?php
class Football_Pool_Admin_Options extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		$options = array(
						array( 'text', __( 'Webmaster', FOOTBALLPOOL_TEXT_DOMAIN ), 'webmaster', __( 'Deze waarde wordt gebruikt voor de shortcode [webmaster].', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Inleg', FOOTBALLPOOL_TEXT_DOMAIN ), 'money', __( 'Als je voor geld speelt, dan is dit het bedrag dat spelers moeten betalen. De shortcode [money] geeft deze waarde weer in de content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Bank', FOOTBALLPOOL_TEXT_DOMAIN ), 'bank', __( 'Als je voor geld speelt, dan is dit de persoon waar het geld moet worden betaald. De shortcode [bank] geeft deze waarde weer in de content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Startdatum', FOOTBALLPOOL_TEXT_DOMAIN ), 'start', __( 'De startdatum van het toernooi. De shortcode [start] geeft deze waarde weer in de content.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Volle Score *', FOOTBALLPOOL_TEXT_DOMAIN ), 'fullpoints', __( 'Aantal punten dat een speler krijgt wanneer de exacte uitkomst van een wedstrijd is voorspeld. De shortcode [fullpoints] geeft deze waarde weer in de content. De waarde wordt ook gebruikt in de berekeningen voor het totaal aantal punten van een speler.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Toto Score *', FOOTBALLPOOL_TEXT_DOMAIN ), 'totopoints', __( 'Aantal punten dat een speler krijgt wanneer de uitkomst van een wedstrijd (winnaar, verliezer, gelijkspel) goed is voorspeld, zonder de exacte uitkomst goed te hebben. De shortcode [totopoints] geeft deze waarde weer in de content. De waarde wordt ook gebruikt in de berekeningen voor het totaal aantal punten van een speler.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'text', __( 'Tijd (in seconden) *', FOOTBALLPOOL_TEXT_DOMAIN ), 'maxperiod', __( 'Een speler kan zijn/haar voorspellingen aanpassen tot aan deze tijd vóór de start van een wedstrijd. De tijd is in seconden, bv. vul voor 15 minuten de waarde 900 in.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
						array( 'checkbox', __( 'Gebruik Pools', FOOTBALLPOOL_TEXT_DOMAIN ), 'use_leagues', __( 'Stel in of de plugin Pools moet gebruiken om de spelers in te delen. Gebruik bv. voor betalende spelers en niet betalende spelers, of voor verschillende afdelingen.') )
					);
		
		self::admin_header( __( 'Plugin Instelllingen', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		if ( Utils::post_string( 'form_action' ) == 'update' ) {
			foreach ( $options as $option ) {
				$value = $option[0] == 'text' ? Utils::post_string( $option[2] ) : Utils::post_integer( $option[2] );
				self::set_value( $option[2], $value );
			}
			self::notice( __( 'Wijzigingen opgeslagen.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		}
		
		self::intro( __( 'Als waarden in de velden die gemarkeerd zijn met een asterisk, worden leeggelaten, dan zal de plugin terugvallen op de waarden zoals die bij installatie van de plugin zijn ingesteld.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::options_form( $options );
		submit_button();
		
		self::admin_footer();
	}
}
?>