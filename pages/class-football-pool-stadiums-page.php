<?php
class Football_Pool_Stadiums_Page {
	public function page_content() {
		$output = '';
		$stadiums = new Football_Pool_Stadiums;

		$stadium_id = Football_Pool_Utils::get_string( 'stadium' );

		$stadium = $stadiums->get_stadium_by_id( $stadium_id );
		if ( is_object( $stadium ) ) {
			// show details for stadium
			$output .= sprintf( '<h1>%s</h1>', htmlentities( $stadium->name, null, 'UTF-8' ) );
			
			if ( $stadium->comments != '' ) {
				$output .= sprintf( '<p class="stadium bio">%s</p>', nl2br( $stadium->comments ) );
			}
			
			$output .= sprintf( '<p>%s</p>', $stadium->HTML_image() );

			// the games played in this stadium
			$plays = $stadium->get_plays();
			if ( count( $plays ) > 0 ) {
				$matches = new Football_Pool_Matches;
				$output .= $matches->print_matches( $plays );
				$output .= sprintf( '<h4>%s</h4>', __( 'matches', FOOTBALLPOOL_TEXT_DOMAIN ) );
			}
			
			$output .= sprintf( '<p><a href="%s">%s</a></p>'
								, get_page_link()
								, __( 'view all venues', FOOTBALLPOOL_TEXT_DOMAIN )
						);
		} else {
			// show all stadiums
			$output .= '<p><ol class="stadium-list">';
			$all_stadiums = $stadiums->get_stadiums();
			$output .= $stadiums->print_lines( $all_stadiums );
			$output .= '</ol></p>';
		}
		
		return apply_filters( 'footballpool_stadiums_page_html', $output, $stadium );
	}
}
