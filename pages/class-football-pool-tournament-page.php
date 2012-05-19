<?php 
class Football_Pool_Tournament_Page {
	public function page_content() {
		$output = '';
		
		$matches = new Football_Pool_Matches;
		$result = $matches->get_info();

		$output .= $matches->print_matches( $result );
		
		return $output;
	}
}
?>