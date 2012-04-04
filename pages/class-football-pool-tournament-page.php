<?php 
class Tournament_Page {
	public function page_content() {
		$output = '';
		
		$matches = new Matches;
		$result = $matches->get_info();

		$output .= $matches->print_matches( $result );
		
		return $output;
	}
}
?>