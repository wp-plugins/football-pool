<?php
class Football_Pool_Team extends Football_Pool_Teams {
	public $id = 0;
	public $name = '';
	public $photo = '';
	public $flag = '';
	public $link = '';
	public $group_ID = 0;
	public $group_name = '';

	public function __construct( $team = 0 ) {
		if ( is_int( $team ) && $team != 0 ) {
			$t = $this->get_team_by_ID( $team );
			if ( is_object( $t ) ) {
				$this->id = $t->id;
				$this->name = __( $t->name, FOOTBALLPOOL_TEXT_DOMAIN );
				$this->photo = $t->photo;
				$this->flag = $t->flag;
				$this->link = $t->link;
				$this->group_ID = $t->group_ID;
				$this->group_name = __( $t->group_name, FOOTBALLPOOL_TEXT_DOMAIN );
			}
		} elseif ( is_array( $team ) ) {
			$this->id = $team['id'];
			$this->name = __( $team['name'], FOOTBALLPOOL_TEXT_DOMAIN );
			$this->photo = $team['photo'];
			$this->flag = $team['flag'];
			$this->link = $team['link'];
			$this->group_ID = $team['groupId'];
			$this->group_name = __( $team['groupName'], FOOTBALLPOOL_TEXT_DOMAIN );
		}
	}
	
	function HTML_thumb() {
		$img_url = FOOTBALLPOOL_PLUGIN_URL . 'assets/images/teams/' . $this->photo;
		return sprintf( '<a class="thumb fp-fancybox" href="%s"><img src="%s" title="%s %s" alt="%s %s" 
								class="teamphotothumb" /></a>',
						$img_url,
						$img_url,
						__( 'klik om te vergroten: ', FOOTBALLPOOL_TEXT_DOMAIN ),
						$this->name,
						__( 'teamfoto voor', FOOTBALLPOOL_TEXT_DOMAIN ),
						$this->name
						);
	}
	
	function HTML_image() {
		return '<img src="' . FOOTBALLPOOL_PLUGIN_URL . 'assets/images/teams/' . str_replace( '_t', '', $this->photo ) . '" title="' . __( 'sluiten', FOOTBALLPOOL_TEXT_DOMAIN ) . '" alt="' . __( 'teamfoto voor', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . $this->name . '" class="teamphoto" onclick="window.close();" />';
	}
	
	function get_plays() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT 
									UNIX_TIMESTAMP(m.playDate) AS matchTimestamp, 
									m.homeTeamId, 
									m.awayTeamId, 
									m.homeScore, 
									m.awayScore, 
									s.id, 
									s.name, 
									t.name AS matchtype, 
									m.nr,
									m.playDate 
								FROM {$prefix}matches m, {$prefix}stadiums s, {$prefix}matchtypes t 
								WHERE m.stadiumId = s.id 
									AND m.matchtypeId = t.id 
									AND (m.homeTeamId = %d OR m.awayTeamId = %d)",
								$this->id,
								$this->id
						);
		
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	function get_stadiums() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT DISTINCT s.id, s.name, s.photo
								FROM {$prefix}stadiums s, {$prefix}matches m 
								WHERE s.id = m.stadiumId 
								AND (m.homeTeamId = %d OR awayTeamId = %d)
								ORDER BY s.name ASC",
								$this->id,
								$this->id
							);
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		
		$stadiums = array();
		foreach ( $rows as $row ) {
			$stadiums[] = new Football_Pool_Stadium( $row );
		}
		
		return $stadiums;
	}
}
?>