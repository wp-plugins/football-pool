<?php
class Football_Pool_Groups_Page {
	public function page_content()
	{
		$group_id = Football_Pool_Utils::get_string( 'group' );
		
		$groups = new Football_Pool_Groups;
		$output = $groups->print_group_standing( $group_id );
		
		if ( $group_id ) {
			// the games for this group
			$output .= '<h2 style="clear: both;">' . __( 'matches in the group stage', FOOTBALLPOOL_TEXT_DOMAIN ) . '</h2>';
			$plays = $groups->get_plays_for_group( $group_id );
			
			$matches = new Football_Pool_Matches;
			$output .= $matches->print_matches( $plays );
			
			$group_names = $groups->get_group_names();
			if ( count( $group_names ) > 1 ) {
				$output .= '<p style="clear: both;"><a href="' . get_page_link() . '">' 
						. __( 'view all groups', FOOTBALLPOOL_TEXT_DOMAIN ) 
						. '</a></p>';
			}
		}
		
		return $output;
	}
}
?>