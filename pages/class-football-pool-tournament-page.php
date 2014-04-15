<?php 
class Football_Pool_Tournament_Page {
	public function page_content() {
		$matches = new Football_Pool_Matches;
		$filtered_matches = apply_filters( 'footballpool_filtered_matches', $matches->matches );
		$output = $matches->print_matches( $filtered_matches );
		return apply_filters( 'footballpool_matches_page_html', $output, $matches->matches );
	}
}
