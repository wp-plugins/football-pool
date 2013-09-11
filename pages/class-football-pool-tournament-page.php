<?php 
class Football_Pool_Tournament_Page {
	public function page_content() {
		$matches = new Football_Pool_Matches;
		return $matches->print_matches( $matches->matches );
	}
}
?>