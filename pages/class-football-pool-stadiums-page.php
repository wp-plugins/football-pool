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
			
			$output .= '<p><a href="' . get_page_link() . '">'
					. __( 'view all venues', FOOTBALLPOOL_TEXT_DOMAIN )
					. '</a></p>';
		}
		else
		{
			// show all stadiums
			$output .= '<p><ol class="stadiumlist">';
			$all_stadiums = $stadiums->get_stadiums();
			$output .= $stadiums->print_lines( $all_stadiums );
			$output .= '</ol></p>';
		}
		
		return $output;
	}
}
?>