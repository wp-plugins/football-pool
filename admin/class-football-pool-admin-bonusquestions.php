<?php
class Football_Pool_Admin_Bonus_Questions extends Football_Pool_Admin {
	public function __construct() {}
	
	public function admin() {
		self::admin_header( __( 'Bonusvragen', FOOTBALLPOOL_TEXT_DOMAIN ), '', 'add new' );
		self::intro( __( 'Bonusvragen toevoegen, wijzigen of verwijderen.', FOOTBALLPOOL_TEXT_DOMAIN ) );// See help for more information.') );
		self::intro( __( 'Bij het wijzigen van bonusvragen worden ook de totalen van spelers en de stand in de pool bijgewerkt. Bij veel deelnemers kan dit enige tijd in beslag nemen.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		//self::help( 'points', __( 'Points', FOOTBALLPOOL_TEXT_DOMAIN ), __( 'Set the award for each question.', FOOTBALLPOOL_TEXT_DOMAIN ) );
		
		$question_id = Football_Pool_Utils::request_int( 'item_id', 0 );
		$bulk_ids = Football_Pool_Utils::post_int_array( 'itemcheck', array() );
		$action = Football_Pool_Utils::request_string( 'action', 'list' );
		
		if ( count( $bulk_ids ) > 0 && $action == '-1' )
			$action = Football_Pool_Utils::request_string( 'action2', 'list' );
		
		switch ( $action ) {
			case 'save':
				// new or updated question
				$question_id = self::update( $question_id );
				self::notice( __( 'Vraag opgeslagen.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				if ( Football_Pool_Utils::post_str( 'submit' ) == 'Save & Close' ) {
					self::view();
					break;
				}
			case 'edit':
				self::edit( $question_id );
				break;
			case 'user-answers-save':
				$id = Football_Pool_Utils::post_integer( 'item_id' );
				self::set_bonus_question_for_users( $id );
				self::update_bonus_question_points();
				
				self::notice( 'Answers updated.', FOOTBALLPOOL_TEXT_DOMAIN );
				if ( Football_Pool_Utils::post_str( 'submit' ) == 'Save & Close' ) {
					self::view();
					break;
				}
			case 'user-answers':
				self::intro( __( 'Op deze pagina kan je instellen of een speler een vraag goed heeft beantwoord. Als je bij een vraag het juiste antwoord hebt opgeslagen, dan wordt deze hier getoond ter referentie.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				self::intro( __( '<strong>Let op:</strong> De punten voor het juist beantwoorden van een vraag worden pas bij het totaal geteld als je dit scherm hebt opgeslagen!', FOOTBALLPOOL_TEXT_DOMAIN ) );
				self::intro( __( 'Je hebt de mogelijkheid om een speler meer of minder punten te geven dan het ingestelde aantal bij de vraag. Vul hiervoor een waarde in in het punten veld; laat het veld leeg als je geen afwijkend aantal wil geven.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				self::edit_user_answers();
				break;
			case 'delete':
				if ( $question_id > 0 ) {
					self::delete( $question_id );
					self::notice(sprintf( __( 'Vraag id:%s verwijderd.', FOOTBALLPOOL_TEXT_DOMAIN ), $question_id ) );
				}
				if ( count( $bulk_ids ) > 0 ) {
					self::delete( $bulk_ids );
					self::notice( sprintf( __("%s vragen verwijderd.", FOOTBALLPOOL_TEXT_DOMAIN ), count( $bulk_ids ) ) );
				}
			default:
				self::intro( __( 'Gebruik de "Antwoorden Spelers" link om te controleren of vragen juist zijn beantwoord door de spelers.', FOOTBALLPOOL_TEXT_DOMAIN ) );
				self::view();
		}
		
		self::admin_footer();
	}
	
	private function edit_user_answers() {
		$id = Football_Pool_Utils::get_integer( 'item_id' );

		if ( $id > 0 ) {
			echo '<form action="" method="post">';
			$pool = new Football_Pool_Pool;
			$question = $pool->get_bonus_question( $id );
			$questiondate = new DateTime( $question['answerBeforeDate'] );
			$answers = $pool->get_bonus_question_answers_for_users( $id );
			
			echo '<h3>', __( 'vraag', FOOTBALLPOOL_TEXT_DOMAIN ), ': ', $question['question'], '</h3>';
			echo '<p>', __( 'antwoord', FOOTBALLPOOL_TEXT_DOMAIN ), ': ', $question['answer'], '<br />';
			echo '<span style="font-size: 75%">', $question['points'], ' ', __( 'punten', FOOTBALLPOOL_TEXT_DOMAIN ), 
						', ', __( 'beantwoorden vóór', FOOTBALLPOOL_TEXT_DOMAIN ), ' ', $questiondate->format( 'Y-m-d H:i' ), '</span></p>';
			
			echo '<table class="widefat">';
			echo '<thead><tr>
					<th>', __( 'speler', FOOTBALLPOOL_TEXT_DOMAIN ), '</th>
					<th>', __( 'antwoord', FOOTBALLPOOL_TEXT_DOMAIN ), '</th>
					<th>', __( 'goed', FOOTBALLPOOL_TEXT_DOMAIN ), '</th>
					<th>', __( 'fout', FOOTBALLPOOL_TEXT_DOMAIN ), '</th>
					<th title="', __( 'Laat leeg als je geen afwijkend aantal punten wil geven voor deze speler.', FOOTBALLPOOL_TEXT_DOMAIN ), '">', __( 'punten', FOOTBALLPOOL_TEXT_DOMAIN ), ' <span class="sup">*)</span></th>
				</tr></thead>';
			echo '<tbody>';
			if ( count( $answers ) > 0 ) {
				foreach ( $answers as $answer ) {
					if ( $answer['correct'] == 1 ) {
						$correct = 'checked="checked" ';
						$wrong = '';
						$input = '';
					} else {
						$correct = '';
						$wrong = 'checked="checked" ';
						$input = 'style="display:none;" ';
					}
					$points = $answer['points'] == 0 ? '' : $answer['points'];
					
					echo '<tr><td>', $answer['name'], '</td><td>', $answer['answer'], '</td>';
					echo '<td><input onchange="togglePoints(this.name)" name="_user_', $answer['userId'], '" value="1" type="radio" ', $correct, ' /></td>';
					echo '<td><input onchange="togglePoints(this.name)" name="_user_', $answer['userId'], '" value="0" type="radio" ', $wrong, ' /></td>';
					echo '<td><input name="_user_', $answer['userId'], '_points" id="_user_', $answer['userId'], '_points" 
								title="', __( 'Laat leeg als je geen afwijkend aantal punten wil geven voor deze speler.', FOOTBALLPOOL_TEXT_DOMAIN ), '"
								value="', $points, '" type="text" size="3" ', $input, ' /></td>';
					echo '</tr>';
				}
			} else {
				echo '<tr><td colspan="4">', __( 'Nog geen antwoorden van spelers.', FOOTBALLPOOL_TEXT_DOMAIN ), '</td></tr>';
			}
			
			echo '</tbody>';
			echo '</table>';
			
			echo '<p>';
			submit_button( 'Save & Close', 'primary', 'submit', false );
			submit_button( null, 'secondary', 'save', false );
			echo '</p>';
			self::hidden_input( 'item_id', $id );
			self::hidden_input( 'action', 'user-answers-save' );
			echo '</form>';
		} else {
			echo '<p>', __( 'Geen vragen, spelers of antwoorden gevonden.', FOOTBALLPOOL_TEXT_DOMAIN ), '</p>';
		}
	}
	
	private function edit( $id ) {
		$exampledate = date( 'Y-m-d 18:00', time() + ( 14 * 24 * 60 * 60 ) );
		$values = array(
						'question' => '',
						'points' => '',
						'answerBeforeDate' => $exampledate,
						'scoreDate'=>'',
						'answer'=>''
						);
		
		$pool = new Football_Pool_Pool();
		$question = $pool->get_bonus_question( $id );
		if ( $question ) {
			$values = $question;
		}
		
		$cols = array(
					array( 'text', __( 'vraag', FOOTBALLPOOL_TEXT_DOMAIN ), 'question', $values['question'], '' ),
					array( 'integer', __( 'punten', FOOTBALLPOOL_TEXT_DOMAIN ), 'points', $values['points'], __( 'Aantal punten dat een speler krijgt voor het juist beantwoorden van de vraag.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
					array( 'date', __( 'beantwoorden vóór', FOOTBALLPOOL_TEXT_DOMAIN ).'<br/><span style="font-size:80%">(bv. ' . $exampledate . ')</span>', 'lastdate', $values['answerBeforeDate'], __( 'Een speler mag deze vraag beantwoorden tot deze datum en tijd.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
					array( 'date', __( 'scoredatum', FOOTBALLPOOL_TEXT_DOMAIN ).'<br/><span style="font-size:80%">(bv. ' . $exampledate . ')</span>', 'scoredate', $values['scoreDate'], __( 'De punten voor de vraag worden bij het totaal aantal punten opgeteld vanaf deze datum/tijd (voor de statistieken). Als deze datum niet is ingevuld, dan worden de punten niet geteld.', FOOTBALLPOOL_TEXT_DOMAIN ) ),
					array( 'text', __( 'antwoord', FOOTBALLPOOL_TEXT_DOMAIN ), 'answer', $values['answer'], __( 'Het juiste antwoord (geldt als referentiewaarde).', FOOTBALLPOOL_TEXT_DOMAIN ) ),
					array( 'hidden', '', 'item_id', $id ),
					array( 'hidden', '', 'action', 'save' )
				);
		self::value_form( $cols );
		echo '<p>';
		submit_button( 'Save & Close', 'primary', 'submit', false );
		submit_button( null, 'secondary', 'save', false );
		echo '</p>';
	}
	
	private function view() {
		$pool = new Football_Pool_Pool();
		$questions = $pool->get_bonus_questions();
		$exampledate = date( 'Y-m-d 18:00', time() + ( 14 * 24 * 60 * 60 ) );

		$cols = array(
					array( 'text', __( 'vraag', FOOTBALLPOOL_TEXT_DOMAIN ), 'question', '' ), 
					array( 'integer', __( 'punten', FOOTBALLPOOL_TEXT_DOMAIN ), 'points', '' ), 
					array( 'date', __( 'beantwoorden vóór', FOOTBALLPOOL_TEXT_DOMAIN ) . '<br/><span style="font-size:80%">(' . __( 'bv.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . $exampledate . ')</span>', 'lastdate', ''), 
					array( 'date', __( 'scoredatum', FOOTBALLPOOL_TEXT_DOMAIN ).'<br/><span style="font-size:80%">('.__( 'bv.', FOOTBALLPOOL_TEXT_DOMAIN ) . ' ' . $exampledate . ')</span>', 'scoredate', '' ), 
					array( 'text', __( 'antwoord', FOOTBALLPOOL_TEXT_DOMAIN ), 'answer', '' )
				);
		
		$rows = array();
		foreach( $questions as $question ) {
			$rows[] = array(
						$question['question'], 
						$question['points'], 
						$question['answerBeforeDate'], 
						$question['scoreDate'], 
						$question['answer'],
						$question['id']
					);
		}
		
		$bulkactions[] = array( 'delete', __( 'Delete', FOOTBALLPOOL_TEXT_DOMAIN ) );
		$rowactions[] = array( 'user-answers', __( 'Antwoorden Spelers', FOOTBALLPOOL_TEXT_DOMAIN ) );
		self::list_table( $cols, $rows, $bulkactions, $rowactions );
	}
	
	private function update( $question_id ) {
		$question = array(
						$question_id,
						Football_Pool_Utils::post_string( 'question' ),
						Football_Pool_Utils::post_string( 'answer' ),
						Football_Pool_Utils::post_int( 'points' ),
						Football_Pool_Utils::post_string( 'lastdate' ),
						Football_Pool_Utils::post_string( 'scoredate' )
					);
		
		$id = self::update_bonus_question( $question );
		self::update_bonus_question_points();
		return $id;
	}
	
	private function delete( $question_id ) {
		if ( is_array( $question_id ) ) {
			foreach ( $question_id as $id ) self::delete_bonus_question( $id );
		} else {
			self::delete_bonus_question( $question_id );
		}
	}
	
	private function delete_bonus_question( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}bonusquestions_useranswers WHERE questionId=%d", $id );
		$wpdb->query( $sql );
		$sql = $wpdb->prepare( "DELETE FROM {$prefix}bonusquestions WHERE id=%d", $id );
		$wpdb->query( $sql );
	}
	
	private function update_bonus_question_points() {
		// scorehistory table for statistics
		self::update_score_history();
	}
	
	private function update_bonus_question( $input ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$id = $input[0];
		$question = stripslashes( $input[1] );
		$answer = $input[2];
		$points = $input[3];
		$date = $input[4];
		$scoredate = $input[5];
		$matchNr = 0;
		
		if ( $id == 0 ) {
			$sql = $wpdb->prepare( "
									INSERT INTO {$prefix}bonusquestions 
										(question, points, answerBeforeDate, scoreDate, answer, matchNr)
									VALUES (%s, %d, %s, NULL, %s, %d)",
							$question, $points, $date, $answer, $matchNr
						);
		} else {
			$sql = $wpdb->prepare( "
									UPDATE {$prefix}bonusquestions SET
										question = %s,
										points = %d,
										answerBeforeDate = %s,
										scoreDate = %s,
										answer = %s,
										matchNr = %d
									WHERE id = %d",
							$question, $points, $date, $scoredate, $answer, $matchNr, $id
						);
		}
		
		$wpdb->query( $sql );
		// quick&dirty work-around for prepare's lack of null value support
		$wpdb->query( "UPDATE {$prefix}bonusquestions SET scoreDate=NULL WHERE scoreDate='0000-00-00 00:00'" );
		
		return ( $id == 0 ) ? $wpdb->insert_id : $id;
	}
	
	private function updateBonusUserAnswers( $questions, $answers, $user ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		foreach ( $questions as $question ) {
			// check for date
			if ( $this->bonus_is_editable( $question['questionDate'] ) && $answers[ $question['id'] ] != '' ) {
				$sql = $wpdb->prepare( "
										REPLACE INTO {$prefix}bonusquestions_useranswers 
										SET userId = %d,
											questionId = %d,
											answer = %s,
											points = 0",
										$user,
										$question['id'],
										$answers[ $question['id'] ]
								);
				$wpdb->query( $sql );
			}
		}
	}
	
	private function set_bonus_question_for_users( $question ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$users = get_users();
		foreach ( $users as $user ) {
			$correct = Football_Pool_Utils::post_integer( '_user_' . $user->ID, -1 );
			$points = Football_Pool_Utils::post_integer( '_user_' . $user->ID . '_points', 0 );
			if ( $correct != -1 ) {
				$sql = $wpdb->prepare( "UPDATE {$prefix}bonusquestions_useranswers 
											SET correct=%d, 
												points=%d 
											WHERE userId=%d AND questionId=%d", 
										$correct, $points, $user->ID, $question
								);
				$wpdb->query( $sql );
			}
		}
	}

}
?>