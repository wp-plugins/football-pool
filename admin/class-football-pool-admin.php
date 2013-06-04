<?php
class Football_Pool_Admin {
	
	public function adminhook_suffix() {
		global $hook_suffix;
		echo "<!-- admin hook for current page is: {$hook_suffix} -->";
	}
	
	private function add_submenu_page( $parent_slug, $page_title, $menu_title
									, $capability, $menu_slug, $class, $toplevel = false ) {
		if ( is_array( $class ) ) {
			$function = $class;
			$help_class = $class[0];
		} else {
			$function = array( $class, 'admin' );
			$help_class = $class;
		}
		
		$menu_level = $toplevel ? 'toplevel' : 'football-pool';
		add_action( "admin_head-{$menu_level}_page_{$menu_slug}", array( $help_class, 'help' ) );
		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}
	
	public function init() {
		$slug = 'footballpool-options';
		$capability = 'manage_football_pool';
		
		// main menu item
		add_menu_page(
			__( 'Football Pool', FOOTBALLPOOL_TEXT_DOMAIN ),
			__( 'Football Pool', FOOTBALLPOOL_TEXT_DOMAIN ),
			$capability, 
			$slug,
			array( 'Football_Pool_Admin_Options', 'admin' ),
			'div'
		);
		
		// submenu pages
		self::add_submenu_page(
			$slug,
			__( 'Football Pool Options', FOOTBALLPOOL_TEXT_DOMAIN ),
			__( 'Plugin Options', FOOTBALLPOOL_TEXT_DOMAIN ),
			$capability, 
			'footballpool-options',
			'Football_Pool_Admin_Options',
			true
		);
		
		self::add_submenu_page(
			$slug,
			__( 'Edit users', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Users', FOOTBALLPOOL_TEXT_DOMAIN ), 
			$capability, 
			'footballpool-users',
			'Football_Pool_Admin_Users'
		);
		
		self::add_submenu_page(
			$slug,
			__( 'Edit matches', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Matches', FOOTBALLPOOL_TEXT_DOMAIN ), 
			$capability, 
			'footballpool-games',
			'Football_Pool_Admin_Games'
		);
		
		self::add_submenu_page(
			$slug,
			__( 'Edit bonus questions', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Questions', FOOTBALLPOOL_TEXT_DOMAIN ), 
			$capability, 
			'footballpool-bonus',
			'Football_Pool_Admin_Bonus_Questions'
		);
		
		self::add_submenu_page(
			$slug,
			__( 'Edit shoutbox', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Shoutbox', FOOTBALLPOOL_TEXT_DOMAIN ), 
			$capability, 
			'footballpool-shoutbox',
			'Football_Pool_Admin_Shoutbox'
		);
		
		self::add_submenu_page(
			$slug,
			__( 'Edit teams', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Teams', FOOTBALLPOOL_TEXT_DOMAIN ), 
			$capability, 
			'footballpool-teams',
			'Football_Pool_Admin_Teams'
		);
		
		// self::add_submenu_page(
			// $slug,
			// __( 'Edit teams', FOOTBALLPOOL_TEXT_DOMAIN ), 
			// __( 'Teams', FOOTBALLPOOL_TEXT_DOMAIN ), 
			// $capability, 
			// 'footballpool-teams-position',
			// 'Football_Pool_Admin_Teams_Position'
		// );
		
		self::add_submenu_page(
			$slug,
			__( 'Edit venues', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Venues', FOOTBALLPOOL_TEXT_DOMAIN ), 
			$capability, 
			'footballpool-venues',
			'Football_Pool_Admin_Stadiums'
		);
		
		self::add_submenu_page(
			$slug,
			__( 'Edit leagues', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Leagues', FOOTBALLPOOL_TEXT_DOMAIN ), 
			$capability, 
			'footballpool-leagues',
			'Football_Pool_Admin_Leagues'
		);
		
		self::add_submenu_page(
			$slug,
			__( 'Edit rankings', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Rankings', FOOTBALLPOOL_TEXT_DOMAIN ), 
			'manage_football_pool', 
			'footballpool-rankings',
			'Football_Pool_Admin_Rankings'
		);
		
		self::add_submenu_page(
			$slug,
			__( 'Edit match types', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Match Types', FOOTBALLPOOL_TEXT_DOMAIN ), 
			$capability, 
			'footballpool-matchtypes',
			'Football_Pool_Admin_Match_Types'
		);
		
		self::add_submenu_page(
			$slug,
			__( 'Edit groups', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Groups', FOOTBALLPOOL_TEXT_DOMAIN ), 
			$capability, 
			'footballpool-groups',
			'Football_Pool_Admin_Groups'
		);
		
		self::add_submenu_page(
			$slug,
			__( 'Help', FOOTBALLPOOL_TEXT_DOMAIN ), 
			__( 'Help', FOOTBALLPOOL_TEXT_DOMAIN ), 
			$capability, 
			'footballpool-help',
			'Football_Pool_Admin_Help'
		);
	}
	
	// tinymce extension
	public function tinymce_addbuttons() {
		// Don't bother doing this stuff if the current user lacks permissions
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) )
			return;
	 
		// Add only in Rich Editor mode
		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'mce_external_plugins', array( 'Football_Pool_Admin', 'add_footballpool_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( 'Football_Pool_Admin', 'register_tinymce_footballpool_button' ) );
		}
	}
	
	public function register_tinymce_footballpool_button( $buttons ) {
		array_push( $buttons, "|", "footballpool" );
		return $buttons;
	}
	
	// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
	public function add_footballpool_tinymce_plugin( $plugin_array ) {
		$plugin_array['footballpool'] = FOOTBALLPOOL_PLUGIN_URL . 'assets/admin/tinymce/editor_plugin.js';
		return $plugin_array;
	}
	// end tinymce
	
	public function add_plugin_settings_link( $links, $file ) {
		if ( $file == plugin_basename( FOOTBALLPOOL_PLUGIN_DIR . 'football-pool.php' ) ) {
			$links[] = '<a href="admin.php?page=footballpool-options">' . __( 'Settings', FOOTBALLPOOL_TEXT_DOMAIN ) . '</a>';
			$links[] = '<a href="admin.php?page=footballpool-help">' . __( 'Help', FOOTBALLPOOL_TEXT_DOMAIN ) . '</a>';
			// $links[] = '<a href="' . FOOTBALLPOOL_DONATE_LINK . '">' . __( 'Donate', FOOTBALLPOOL_TEXT_DOMAIN ) . '</a>';
		}

		return $links;
	}
	
	public function get_value( $key, $default = '' ) {
		return Football_Pool_Utils::get_fp_option( $key, $default );
	}
	
	public function set_value( $key, $value, $type = 'text' ) {
		Football_Pool_Utils::update_fp_option( $key, $value );
	}
	
	// use type 'updated' for yellow message and type 'error' or 'important' for the red one
	public function notice( $msg, $type = 'updated', $fade = true ) {
		if ( $type == 'important' ) $type = 'error';
		echo '<div class="', esc_attr( $type ), ( $fade ? ' fade' : '' ), '"><p>', $msg, '</p></div>';
	}
	
	public function image_input( $label, $key, $value, $description = '', $type = 'regular-text' ) {
		$key = esc_attr( $key );
		echo '<script type="text/javascript">
			jQuery( document ).ready( function() {
				window.send_to_editor_restore = window.send_to_editor;
				
				jQuery( "#', $key, '_button" ).click( function() {
					post_id = jQuery( "#post_ID" ).val();
					tb_show( "", "media-upload.php?football_pool_admin=footballpool-bonus&amp;post_id=0&amp;type=image&amp;TB_iframe=true" );
					
					window.send_to_editor = window.send_to_editor_', $key, ';
					
					return false;
				});
				 
				window.send_to_editor_', $key, ' = function( html ) {
					imgurl = jQuery( "img", html ).attr( "src" );
					if ( imgurl == "" && jQuery( "#src" ) ) imgurl = jQuery( "#src" ).val();
					
					jQuery( "#', $key, '" ).val( imgurl );
					tb_remove();
					
					window.send_to_editor = window.send_to_editor_restore;
				}
			});
			</script>';
		
		$input = sprintf( '<input name="%s" type="text" id="%s" value="%s" title="%s" class="%s">
							<input id="%s_button" type="button" value="%s">'
							, $key
							, $key
							, esc_attr( $value )
							, esc_attr( $value )
							, esc_attr( $type )
							, $key
							, __( 'Choose Image', FOOTBALLPOOL_TEXT_DOMAIN )
						);
		echo self::option_row( $key, $label, $input, $description );
	}
	
	public function checkbox_input( $label, $key, $checked, $description = ''
									, $extra_attr = '', $depends_on = '' ) {
		$input = sprintf( '<input name="%s" type="checkbox" id="%s" value="1" %s %s>'
							, esc_attr( $key )
							, esc_attr( $key )
							, ( $checked ? 'checked="checked" ' : '' )
							, $extra_attr
						);
		echo self::option_row( $key, $label, $input, $description, $depends_on );
	}
	
	public function dropdown( $key, $value, $options, $extra_attr = '', $multi = 'single' ) {
		$i = 0;
		$multiple = '';
		$name = esc_attr( $key );
		if ( $multi == 'multi' ) {
			$multiple = 'multiple="multiple" size="6" class="fp-multi-select"';
			$name .= '[]';
		}
		$output = sprintf( '<select id="%s" name="%s" %s>', esc_attr( $key ), $name, $multiple );
		
		foreach ( $options as $option ) {
			if ( is_array( $extra_attr ) ) {
				$extra = isset( $extra_attr[$i] ) ? $extra_attr[$i] : '';
			} else {
				$extra = $extra_attr;
			}
			
			$selected = ( self::check_selected_value( $value, $option['value'] ) ? 'selected="selected" ' : '' );
			$output .= sprintf( '<option id="answer_%d" value="%s" %s %s>%s</option>'
								, $i
								, esc_attr( $option['value'] )
								, $selected
								, $extra
								, $option['text']
						);
			$i++;
		}
		$output .= '</select>';
		
		return $output;
	}
	
	private function check_selected_value( $check_value, $option_value ) {
		if ( is_array( $check_value ) ) {
			return in_array( $option_value, $check_value );
		} else {
			return ( $option_value == $check_value );
		}
	}
	
	public function multiselect_input( $label, $key, $value, $options, $description = '', 
									$extra_attr = '', $depends_on = '' ) {
		echo self::option_row( $key, $label, self::dropdown( $key, $value, $options, $extra_attr, 'multi' )
								, $description, $depends_on );
	}
	
	public function dropdown_input( $label, $key, $value, $options, $description = '', 
									$extra_attr = '', $depends_on = '' ) {
		echo self::option_row( $key, $label, self::dropdown( $key, $value, $options, $extra_attr )
								, $description, $depends_on );
	}
	
	public function radiolist_input( $label, $key, $value, $options, $description = '', 
									$extra_attr = '', $depends_on = '' ) {
		$hide = self::hide_input( $depends_on ) ? ' style="display:none;"' : '';
		
		$i = 0;
		$label_extra = sprintf( '_answer_%d', $i );
		$input = '';
		foreach ( $options as $option ) {
			if ( is_array( $extra_attr ) ) {
				$extra = isset( $extra_attr[$i] ) ? $extra_attr[$i] : '';
			} else {
				$extra = $extra_attr;
			}
			$selected = ( self::check_selected_value( $value, $option['value'] ) ? 'checked="checked" ' : '' );
			$input .= sprintf( '<label class="radio"><input name="%s" type="radio" id="%s_answer_%d" 
								value="%s" %s %s> %s</label><br />'
								, esc_attr( $key )
								, esc_attr( $key )
								, $i++
								, esc_attr( $option['value'] )
								, $selected
								, $extra
								, $option['text']
						);
		}
		
		echo self::option_row( $key, $label, $input, $description, $depends_on, $label_extra );
	}
	
	public function hidden_input( $key, $value ) {
		echo '<input type="hidden" name="', esc_attr( $key ), '" id="', esc_attr( $key ), '" value="', esc_attr( $value ), '">';
	}
	
	public function no_input( $label, $value, $description ) {
		echo '<tr valign="top">
			<th scope="row"><label>', $label, '</label></th>
			<td>', $value, '</td>
			<td><span class="description">', $description, '</span></td>
			</tr>';
	}
	
	// accepts a date in Y-m-d H:i format and changes it to UTC
	public function gmt_from_date( $date_string ) {
		return Football_Pool_Utils::gmt_from_date( $date_string );
	}
	
	// accepts a date in Y-m-d H:i format and changes it to local time according to WP's timezone setting
	public function date_from_gmt( $date_string ) {
		return Football_Pool_Utils::date_from_gmt( $date_string );
	}
	
	// helper function for the date_time input. 
	// returns the combined date(time) string from the individual inputs
	public function make_date_from_input( $input_name, $type = 'datetime' ) {
		$y = Football_Pool_Utils::post_integer( $input_name . '_y' );
		$m = Football_Pool_Utils::post_integer( $input_name . '_m' );
		$d = Football_Pool_Utils::post_integer( $input_name . '_d' );
		$value = ( $y != 0 && $m != 0 && $d != 0 ) ? sprintf( '%04d-%02d-%02d', $y, $m, $d ) : '';
		
		if ( $value != '' && $type == 'datetime' ) {
			$h = Football_Pool_Utils::post_integer( $input_name . '_h', -1 );
			$i = Football_Pool_Utils::post_integer( $input_name . '_i', -1 );
			$value = ( $h != -1 && $i != -1 ) ? sprintf( '%s %02d:%02d', $value, $h, $i ) : '';
		}
		
		return $value;
	}
	
	public function datetime_input( $label, $key, $value, $description = '', $extra_attr = ''
									, $depends_on = '' ) {
		if ( $value != '' ) {
			//$date = DateTime::createFromFormat( 'Y-m-d H:i', $value );
			$date = new DateTime( self::date_from_gmt ( $value ) );
			$year = $date->format( 'Y' );
			$month = $date->format( 'm' );
			$day = $date->format( 'd');
			$hour = $date->format( 'H' );
			$minute = $date->format( 'i' );
		} else {
			$year = $month = $day = $hour = $minute = '';
		}
		
		$input = sprintf( '<input name="%s_y" type="text" id="%s_y" value="%s" class="with-hint date-y"
							title="yyyy" maxlength="4">'
							, esc_attr( $key ), esc_attr( $key ), esc_attr( $year )
				);
		$input .= '-';
		$input .= sprintf( '<input name="%s_m" type="text" id="%s_m" value="%s" class="with-hint date-m"
							title="mm" maxlength="2">'
							, esc_attr( $key )
							, esc_attr( $key )
							, esc_attr( $month )
				);
		$input .= '-';
		$input .= sprintf( '<input name="%s_d" type="text" id="%s_d" value="%s" class="with-hint date-d"
							title="dd" maxlength="2">'
							, esc_attr( $key )
							, esc_attr( $key )
							, esc_attr( $day )
				);
		$input .= '&nbsp;';
		$input .= sprintf( '<input name="%s_h" type="text" id="%s_m" value="%s" class="with-hint date-h"
							title="hr" maxlength="2">'
							, esc_attr( $key )
							, esc_attr( $key )
							, esc_attr( $hour )
				);
		$input .= ':';
		$input .= sprintf( '<input name="%s_i" type="text" id="%s_d" value="%s" class="with-hint date-i"
							title="mn" maxlength="2">'
							, esc_attr( $key )
							, esc_attr( $key )
							, esc_attr( $minute )
				);
		
		echo self::option_row( $key, $label, $input, $description, $depends_on );
	}
	
	public function textarea_field( $key, $value, $type = '' ) {
		return sprintf( '<textarea name="%s" class="%s" cols="50" rows="5">%s</textarea>'
							, esc_attr( $key ), $type, $value
					);
	}
	
	public function textarea_input( $label, $key, $value, $description = '', $type = '', $depends_on = '' ) {
		echo self::option_row( $key, $label, self::textarea_field( $key, $value, $type )
								, $description, $depends_on );
	}
	
	public function text_input_field( $key, $value, $type = 'regular-text', $capability = '' ) {
		if ( $capability == '' || ( $capability != '' && current_user_can( $capability ) ) ) {
			$output = '<input name="' . esc_attr( $key ) . '" type="text" id="' . esc_attr( $key ) 
					. '" value="' . esc_attr( $value ) . '" class="' . esc_attr( $type ) . '" />';
		} else {
			$output = $value;
		}
		return $output;
	}
	
	public function text_input( $label, $key, $value, $description = ''
								, $type = 'regular-text', $depends_on = '' ) {
		echo self::option_row( $key, $label, self::text_input_field( $key, $value, $type )
								, $description, $depends_on );
	}
	
	private function hide_input( $depends_on ) {
		if ( is_bool( $depends_on ) ) {
			$hide = $depends_on;
		} elseif ( is_array( $depends_on ) ) {
			$hide = true;
			foreach ( $depends_on as $key => $val ) {
				$hide &= (string)self::get_value( $key ) == (string)$val;
			}
		} else {
			$hide = ( $depends_on != '' && (string)self::get_value( $depends_on ) == '0' );
		}
		
		return $hide;
	}
	
	private function option_row( $id, $label, $input, $description, $depends_on = '', $label_extra = '' ) {
		$hide = self::hide_input( $depends_on ) ? ' style="display: none"' : '';
		$class = ( $depends_on == '' ) ? '' : ' class="no-border"';
		
		$option = sprintf( '<th scope="row"><label for="%s%s">%s</label></th>'
							, esc_attr( $id ), $label_extra, $label );
		$input = sprintf( '<td>%s</td>', $input );
		$description = sprintf( '<td><span class="description">%s</span></td>', $description );
		
		return sprintf( '<tr%s%s id="r-%s" valign="top">%s%s%s</tr>'
						, $hide, $class, esc_attr( $id ), $option, $input, $description
				);
	}
	
	public function show_option( $option ) {
		if ( is_array( $option[0] ) ) {
			$type = $option[0][0];
		} else {
			$type = $option[0];
		}
		
		switch ( $type ) {
			case 'multi-list':
			case 'multi-select':
			case 'multi-selectbox':
				self::multiselect_input( $option[1], $option[2], self::get_value( $option[2] ), $option[3], $option[4], $option[5], ( isset( $option[6] ) ? $option[6] : '' ) );
				break;
			case 'dropdownlist':
			case 'dropdown':
			case 'select':
			case 'selectbox':
				self::dropdown_input( $option[1], $option[2], self::get_value( $option[2] ), $option[3], $option[4], $option[5], ( isset( $option[6] ) ? $option[6] : '' ) );
				break;
			case 'radiolist':
				self::radiolist_input( $option[1], $option[2], self::get_value( $option[2] ), $option[3], $option[4], isset( $option[5] ) ? $option[5] : '', isset( $option[6] ) ? $option[6] : '' );
				break;
			case 'checkbox':
				self::checkbox_input( $option[1], $option[2], (boolean) self::get_value( $option[2] ), $option[3], ( isset( $option[4] ) ? $option[4] : '' ), ( isset( $option[5] ) ? $option[5] : '' ) );
				break;
			case 'datetime':
				self::datetime_input( $option[1], $option[2], self::get_value( $option[2] ), $option[3], ( isset( $option[4] ) ? $option[4] : '' ), ( isset( $option[5] ) ? $option[5] : '' ) );
				break;
			case 'textarea':
			case 'multiline':
				self::textarea_input( $option[1], $option[2], self::get_value( $option[2] ), $option[3], '', ( isset( $option[4] ) ? $option[4] : '' ) );
				break;
			case 'integer':
			case 'string':
			case 'text':
			default:
				self::text_input( $option[1], $option[2], self::get_value( $option[2] ), $option[3], 'regular-text', ( isset( $option[4] ) ? $option[4] : '' ) );
				break;
		}
	}
	
	public function show_value( $option ) {
		if ( is_array( $option[0] ) ) {
			$type = $option[0][0];
		} else {
			$type = $option[0];
		}
		
		switch ( $type ) {
			case 'no_input':
				self::no_input( $option[1], $option[3], $option[4] );
				break;
			case 'dropdownlist':
			case 'dropdown':
			case 'select':
			case 'selectbox':
				self::dropdown_input( $option[1], $option[2], $option[3], $option[4], $option[5], isset( $option[6] ) ? $option[6] : '' );
				break;
			case 'radiolist':
				self::radiolist_input( $option[1], $option[2], $option[3], $option[4], isset( $option[5] ) ? $option[5] : '', isset( $option[6] ) ? $option[6] : '' );
				break;
			case 'checkbox':
				self::checkbox_input( $option[1], $option[2], $option[3], $option[4], isset( $option[5] ) ? $option[5] : '' );
				break;
			case 'hidden':
				self::hidden_input( $option[2], $option[3] );
				break;
			case 'image':
				self::image_input( $option[1], $option[2], $option[3], $option[4] );
				break;
			case 'date':
			case 'datetime':
				self::datetime_input( $option[1], $option[2], $option[3], ( isset( $option[4] ) ? $option[4] : '' ) );
				break;
			case 'multiline':
			case 'textarea':
				self::textarea_input( $option[1], $option[2], $option[3], $option[4], ( isset( $option[5] ) ? $option[5] : '' ), ( isset( $option[6] ) ? $option[6] : '' ) );
				break;
			case 'integer':
			case 'string':
			case 'text':
			default:
				self::text_input( $option[1], $option[2], $option[3], $option[4], ( isset( $option[5] ) ? $option[5] : 'regular-text' ), ( isset( $option[6] ) ? $option[6] : '' ) );
				break;
		}
	}
	
	public function intro( $txt ) {
		echo sprintf( '<p>%s</p>', $txt );
	}
	
	// overwrite in the individual help pages
	public function help() {
		self::add_help_tabs();
	}
	
	// Define a method named 'help' on each admin page that calls this method with 
	// the tab definition (array of tabs) and an optional sidebar.
	// Don't forget to add the admin_head-hook!
	public function add_help_tabs( $help_tabs = '', $help_sidebar = '' ) {
		if ( ! is_array( $help_tabs ) ) return;
		
		$screen = get_current_screen();
		foreach ( $help_tabs as $help_tab ) {
			$screen->add_help_tab(
						array(
							'id' => $help_tab['id'],
							'title' => $help_tab['title'],
							'content' => $help_tab['content']
						)
					);
		}
		
		if ( $help_sidebar != '' ) {
			$screen->set_help_sidebar(
							sprintf( 
									'<p><strong>%s</strong></p><p>%s</p>' 
									, __( 'For more information:' )
									, $help_sidebar
							)
						);
		}
	}
	
	public function admin_sectiontitle( $title ) {
		echo '<h3>', $title, '</h3>';
	}
	
	public function admin_header( $title, $subtitle = '', $addnew = '', $extra = '' ) {
		echo '<div class="wrap fp-admin">';
		
		// season greetings
		$season = '';
		$month = (int) date( 'm' );
		$day = (int) date( 'd' );
		if ( $month == 1 && ( $day >= 1 && $day <= 5 ) ) {
			$season = 'newyear';
		} elseif ( $month == 12 && ( $day == 25 || $day == 26 ) ) {
			$season = 'xmas';
		} elseif ( $month == 11 && $day == 31 ) {
			$season = 'halloween';
		}
		
		if ( $season !== '' ) {
			echo '<style type="text/css"> #icon-footballpool-options.icon32 { background: url(', FOOTBALLPOOL_PLUGIN_URL, 'assets/admin/images/admin-menu-32-', $season, '.png) 0 0 no-repeat; } </style>';
		}
		// end season
		
		screen_icon();
		
		$page = Football_Pool_Utils::get_string( 'page' );
		if ( $addnew == 'add new' ) {
			$addnew = "<a class='add-new-h2' href='?page={$page}&amp;action=edit'>" 
					. __( 'Add New', FOOTBALLPOOL_TEXT_DOMAIN ) . "</a>";
		}
		
		if ( $subtitle != '' ) {
			$subtitle = sprintf( '<span class="subtitle">%s</span>', $subtitle );
		}
		
		printf( '<h2>%s%s%s</h2>', $title, $subtitle, $addnew );

		echo $extra;
		echo '<form action="" method="post">';
		echo '<input type="hidden" name="action" id="action" value="update" />';
		wp_nonce_field( FOOTBALLPOOL_NONCE_ADMIN );
	}
	
	public function admin_footer() {
		echo '</form></div>';
	}
	
	public function bulk_actions( $actions, $name = 'action' ) {
		if ( count($actions) > 0 ) {
			echo '<div class="tablenav top"><div class="alignleft actions"><select id="', $name, '" name="', $name, '">';
			echo '<option selected="selected" value="-1">Bulk Actions</option>';
			foreach ( $actions as $action ) {
				printf( '<option value="%s" bulk-msg="%s">%s</option>'
						, $action[0]
						, ( isset( $action[2] ) ? $action[2] : '' )
						, $action[1]
				);
			}
			echo "</select><input onclick=\"return bulk_action_warning( '{$name}' )\" type='submit' value='Apply' class='button-secondary action' id='do{$name}' name='' />";
			echo '</div><br class="clear"></div>';
		}
	}
	
	protected function list_table( $cols, $rows, $bulkactions = array(), $rowactions = array() ) {
		self::bulk_actions( $bulkactions, 'action' );
		echo "<table cellspacing='0' class='wp-list-table widefat fixed'>";
		self::list_table_def( $cols, 'head' );
		self::list_table_def( $cols, 'foot' );
		self::list_table_body( $cols, $rows, $rowactions );
		echo '</table>';
		self::bulk_actions( $bulkactions, 'action2' );
	}
	
	protected function list_table_def( $cols, $tag ) {
		echo "<t{$tag}><tr>";
		echo '
			<th class="manage-column column-cb check-column" id="cb" scope="col">
				<input type="checkbox">
			</th>';
		
		foreach ( $cols as $col ) {
			echo '<th id="', esc_attr( $col[2] ), '" class="manage-column column-', esc_attr( $col[2] ), '" scope="col">', $col[1], '</th>';
		}
		echo "</tr></t{$tag}>";
	}

	protected function list_table_body( $cols, $rows, $rowactions ) {
		echo "<tbody id='the-list'>";
		
		$r = count( $rows );
		$c = count( $cols );
		$page = Football_Pool_Utils::get_string( 'page' );
		
		if ( $r == 0 ) {
			echo "<tr><td colspan='", $c+1, "'>", __( 'no data', FOOTBALLPOOL_TEXT_DOMAIN ), "</td></tr>";
		} else {
			for ( $i = 0; $i < $r; $i++ ) {
				$row_class = ( $i % 2 == 0 ) ? 'alternate' : '';
				echo "
					<tr valign='middle' class='{$row_class}' id='row-{$i}'>
					<th class='check-column' scope='row'>
						<input type='checkbox' value='{$rows[$i][$c]}' name='itemcheck[]'>
					</th>";
				for ( $j = 0; $j < $c; $j++ ) {
					echo "<td class='column-{$cols[$j][2]}'>";
					if ( $j == 0 ) {
						echo '<strong><a title="Edit “', esc_attr( $rows[$i][$j] ), '”" href="?page=', esc_attr( $page ), '&amp;action=edit&amp;item_id=', esc_attr( $rows[$i][$c] ), '" class="row-title">';
					}
					
					switch ( $cols[$j][0] ) {
						case 'boolean':
							$value = $rows[$i][$j] == 1 ? 
											__( 'yes', FOOTBALLPOOL_TEXT_DOMAIN ) : 
											__( 'no', FOOTBALLPOOL_TEXT_DOMAIN );
							break;
						case 'text':
						default:
							$value = $rows[$i][$j];
					}
					echo $value;
					
					if ( $j == 0 ) {
						$row_action_url = sprintf( '?page=%s&amp;action=edit&amp;item_id=%s'
													, esc_attr( $page )
													, esc_attr( $rows[$i][$c] )
											);
						$row_action_url = wp_nonce_url( $row_action_url, FOOTBALLPOOL_NONCE_ADMIN );
						echo '</a></strong><br>
								<div class="row-actions">
									<span class="edit">
										<a href="', $row_action_url, '">Edit</a> | 
									</span>';
						foreach ( $rowactions as $action ) {
							$row_action_url = sprintf( '?page=%s&amp;action=%s&amp;item_id=%s'
														, esc_attr( $page )
														, esc_attr( $action[0] )
														, esc_attr( $rows[$i][$c] )
												);
							$row_action_url = wp_nonce_url( $row_action_url, FOOTBALLPOOL_NONCE_ADMIN );
							echo '<span class="edit">
									<a href="', $row_action_url, '">', $action[1], '</a> | 
								</span>';
						}
						$row_action_url = sprintf( '?page=%s&amp;action=delete&amp;item_id=%s'
													, esc_attr( $page )
													, esc_attr( $rows[$i][$c] )
											);
						$row_action_url = wp_nonce_url( $row_action_url, FOOTBALLPOOL_NONCE_ADMIN );
						echo "<span class='delete'>
									<a onclick=\"return confirm( 'You are about to delete this item. \'Cancel\' to stop, \'OK\' to delete.' )\" href='", $row_action_url, "' class='submitdelete'>Delete</a>
								</span>
							</div>";
					}
					
					echo "</td>";
				}
				echo "</tr>";
			}
		}
		echo '</tbody>';
	}
	
	public function value_form( $values ) {
		echo '<table class="form-table">';
		foreach ( $values as $value ) {
			self::show_value( $value );
		}
		echo '</table>';
	}

	public function options_form( $values ) {
		echo '<table class="form-table fp-options">';
		foreach ( $values as $value ) {
			self::show_option( $value );
		}
		echo '</table>';
	}
	
	public function empty_table( $table_name = '' ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $table_name == '' ) return false;
		
		$cache_key = 'fp_delete_method';
		$delete_method = wp_cache_get( $cache_key );
		
		if ( $delete_method === false ) {
			$delete_method = 'TRUNCATE TABLE';
			wp_cache_set( $cache_key, $delete_method );
		}
		
		$sql  = "{$delete_method} {$prefix}{$table_name}";
		$check = $wpdb->query( $sql );
		// fix if user has no TRUNCATE rights
		if ( $check === false ) {
			$delete_method = 'DELETE FROM';
			wp_cache_set( $cache_key, $delete_method );
			
			$sql  = "{$delete_method} {$prefix}{$table_name}";
			$check = ( $wpdb->query( $sql ) !== false );
		}
		
		return $check;
	}
	
	public function recalculate_scorehistory_iframe() {
		$url = FOOTBALLPOOL_PLUGIN_URL . 'admin/calculate-score-history.php';
		$url = wp_nonce_url( $url, FOOTBALLPOOL_NONCE_SCORE_CALC );
		
		printf( '<script>
					jQuery.colorbox( { 
										iframe: true, 
										href: "%s", 
										overlayClose: false, 
										escKey: false, 
										arrowKey: false,
										close: "%s",
										innerWidth: "500px",
										innerHeight: "250px",
									} ); 
				</script>'
				, $url
				, __( 'close', FOOTBALLPOOL_TEXT_DOMAIN )
		);
	}
	
	private function recalculate_manual() {
		self::secondary_button( 
			__( 'Recalculate Scores', FOOTBALLPOOL_TEXT_DOMAIN ), 
			wp_nonce_url( '?page=footballpool-options&recalculate=yes', FOOTBALLPOOL_NONCE_ADMIN ), 
			true, 
			'link' 
		);
	}
	
	public function update_score_history( $force = 'no' ) {
		$auto_calc = Football_Pool_Utils::get_fp_option( 'auto_calculation'
														, FOOTBALLPOOL_RANKING_AUTOCALCULATION
														, 'int' );
		
		if ( $auto_calc == 1 || $force == 'force' ) {
			self::recalculate_scorehistory_iframe();
		} else {
			self::recalculate_manual();
		}
		return true;
	}
	
	public function update_ranking_log( $ranking_id, $old_set, $new_set, $log_message
										, $preserve_keys = 'no' ) {
		$log = ( ! is_array( $new_set ) || ! is_array( $old_set ) 
					|| count( $new_set ) != count( $old_set ) );
					
		if ( $preserve_keys == 'assoc' || $preserve_keys == 'preserve keys' ) {
			$log = ( $log || count( array_diff_assoc( $new_set, $old_set ) ) > 0 );
		} else {
			$log = ( $log || count( array_diff( $new_set, $old_set ) ) > 0 );
		}
		
		if ( $log ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}rankings_updatelog 
										( ranking_id, log_message, log_date )
									VALUES ( %d, %s, NOW() )"
									, $ranking_id, $log_message
							);
			
			$wpdb->query( $sql );
		}
	}
	
	private function get_button_action_val( $action ) {
		$onclick_val = '';
		
		if ( is_array( $action ) ) {
			$action_val = array_shift( $action );
			if ( count( $action ) > 0 ) {
				foreach ( $action as $val ) {
					$onclick_val .= "{$val};";
				}
			}
		} else {
			$action_val = $action;
		}
		return array( $action_val, $onclick_val );
	}
	
	// this function returns HTML for a secondary button, rather than echoing it
	public function link_button( $text, $action, $wrap = false, $other_attributes = null ) {
		$actions = self::get_button_action_val( $action );
		$action_val  = $actions[0];
		$onclick_val = $actions[1];
		
		$attributes = '';
		if ( is_array( $other_attributes ) ) {
			foreach( $other_attributes as $key => $value ) {
				$attributes .= $key . '="' . esc_attr( $value ) . '" ';
			}
		} elseif ( ! empty( $other_attributes ) ) {
			$attributes = $other_attributes;
		}
		
		$action_val = "location.href='{$action_val}'";
		$button = sprintf( '<input type="button" onclick="%s;%s" 
									class="button-secondary" value="%s" %s/>'
							, $action_val
							, $onclick_val
							, esc_attr( $text )
							, $attributes
					);
		if ( $wrap ) {
			$button = '<p class="submit">' . $button . '</p>';
		}
		
		return $button;
	}
	
	public function secondary_button( $text, $action, $wrap = false, $type = 'button'
									, $other_attributes = null ) {
		$actions = self::get_button_action_val( $action );
		$action_val  = $actions[0];
		$onclick_val = $actions[1];
				
		if ( $type == 'button' ) {
			$onclick_val = "jQuery('#action, #form_action').val('{$action_val}');" . $onclick_val;
			$atts = array( "onclick" => $onclick_val );
			
			if ( is_array( $other_attributes ) ) {
				foreach( $other_attributes as $key => $value ) {
					$atts[$key] = $value;
				}
			}
			
			submit_button( 
					$text, 
					'secondary', 
					$action_val, 
					$wrap, 
					$atts 
			);
		} elseif ( $type == 'link' || $type == 'js-button' ) {
			echo self::link_button( $text, $action, $wrap, $other_attributes );
		}
	}
	
	public function primary_button( $text, $action, $wrap = false ) {
		$onclick_val = '';
		
		if ( is_array( $action ) ) {
			$action_val = array_shift( $action );
			if ( count( $action ) > 0 ) {
				foreach ( $action as $val ) {
					$onclick_val .= "{$val};";
				}
			}
		} else {
			$action_val = $action;
		}
		
		$onclick_val = "jQuery('#action, #form_action').val('{$action_val}');" . $onclick_val;
		
		submit_button( 
				$text, 
				'primary', 
				$action_val, 
				$wrap, 
				array( "onclick" => $onclick_val ) 
		);
	}
	
	public function cancel_button( $wrap = false, $text = '' ) {
		if ( $text == '' ) $text = __( 'Cancel', FOOTBALLPOOL_TEXT_DOMAIN );
		self::secondary_button( $text, 'cancel', $wrap );
	}
	
	public function donate_button( $return_type = 'echo' ) {
		$str = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="J7YJ9VMSLYTBJ">
			<input type="image" src="https://www.paypalobjects.com/en_GB/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal — The safer, easier way to pay online.">
			<img alt="" border="0" src="https://www.paypalobjects.com/nl_NL/i/scr/pixel.gif" width="1" height="1">
			</form>';
		if ( $return_type == 'echo' ) {
			echo $str;
		} else {
			return $str;
		}
	}
	
	/**
	 * Somewhat hacky way of replacing "Insert into Post" with "Use Image"
	 *
	 * @param string $translated_text text that has already been translated (normally passed straight through)
	 * @param string $source_text text as it is in the code
	 * @param string $domain domain of the text
	 * @author Modern Tribe, Inc. (Peter Chester)
	 */
	public function replace_text_in_thickbox( $translated_text, $source_text, $domain ) {
		if ( Football_Pool_Utils::get_string( 'football_pool_admin' ) == 'footballpool-bonus' ) {
			if ( 'Insert into Post' == $source_text ) {
				return __( 'Use Image', FOOTBALLPOOL_TEXT_DOMAIN );
			}
		}
		return $translated_text;
	}
	
}
?>