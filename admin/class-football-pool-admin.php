<?php
class Football_Pool_Admin {
	public function add_body_class( $classes ) {
		global $hook_suffix;
		if ( strpos( $hook_suffix, 'footballpool' ) !== false ) $classes .= 'football-pool';
		return $classes;
	}
	
	public function adminhook_suffix() {
		// for debugging
		global $hook_suffix;
		echo "<!-- admin hook for current page is: {$hook_suffix} -->";
	}
	
	public function set_screen_options( $status, $option, $value ) {
		return $value;
	}
	
	public function get_screen_option( $option, $type = 'int' ) {
		$default_value = false;
		$screen = get_current_screen();
		
		$screen_option = $screen->get_option( $option, 'option' );
		$option_value = get_user_meta( get_current_user_id(), $screen_option, true );
		
		$default_value = empty ( $option_value );
		if ( ! $default_value && $type == 'int' ) $option_value = (int) $option_value;
		
		if ( $default_value ) $option_value = $screen->get_option( $option, 'default' );
		
		return $option_value;
	}
	
	private function add_submenu_page( $parent_slug, $page_title, $menu_title
									, $capability, $menu_slug, $class, $toplevel = false ) {
		if ( is_array( $class ) ) {
			$function = array( $class['admin'], 'admin' );
			$help_class = $class['help'];
			$screen_options_class = $class['screen_options'];
		} else {
			$function = array( $class, 'admin' );
			$help_class = $screen_options_class = $class;
		}
		
		$hook = add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		
		// help tab
		if ( method_exists( $help_class, 'help' ) ) {
			$menu_level = $toplevel ? 'toplevel' : 'football-pool';
			add_action( "admin_head-{$menu_level}_page_{$menu_slug}", array( $help_class, 'help' ) );
		}
		
		// screen options
		if ( $hook && method_exists( $screen_options_class, 'screen_options' ) ) {
			add_action( "load-{$hook}", array( $screen_options_class, 'screen_options' ) );
		}
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
		
		if ( FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX ) {
			self::add_submenu_page(
				$slug,
				'Score Calculation',
				'Score Calculation',
				$capability, 
				'footballpool-score-calculation',
				'Football_Pool_Admin_Score_Calculation'
			);
		}
		
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
	
	public function initialize_wp_media() {
		if ( FOOTBALLPOOL_WP_MEDIA ) {
			wp_enqueue_media();
		} else {
			if ( ! wp_script_is( 'media-upload', 'queue' ) ) {
				wp_enqueue_script( 'media-upload' );
			}
			if ( ! wp_script_is( 'thickbox', 'queue' ) ) {
				wp_enqueue_script( 'thickbox' );
			}
			if ( ! wp_style_is( 'thickbox', 'queue' ) ) {
				wp_enqueue_style( 'thickbox' );
			}
		}
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
		$plugin_array['footballpool'] = FOOTBALLPOOL_PLUGIN_URL . 'assets/admin/tinymce/editor_plugin.min.js';
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
		echo '<div id="message" class="', esc_attr( $type ), ( $fade ? ' fade' : '' ), '"><p>', $msg, '</p></div>';
	}
	
	private function image_input_WP3_5( $label, $key, $value, $description, $type ) {
		$key = esc_attr( $key );
		$title = __( 'Choose Image', FOOTBALLPOOL_TEXT_DOMAIN );
		// based on http://mikejolley.com/2012/12/using-the-new-wordpress-3-5-media-uploader-in-plugins/
		echo "<script type='text/javascript'>
			jQuery( document ).ready( function() {
				var file_frame;
				jQuery( '#{$key}_button ' ).live( 'click', function( event ) {
					event.preventDefault();
					
					if ( file_frame ) {
						file_frame.open();
						return;
					}
				 
					file_frame = wp.media.frames.file_frame = wp.media( {
						title: '{$title}',//jQuery( this ).data( 'uploader_title' ),
						button: {
							text: jQuery( this ).data( 'uploader_button_text' ),
						},
						multiple: false  
					} );
				 
					file_frame.on( 'select', function() {
						attachment = file_frame.state().get( 'selection' ).first().toJSON();
						// jQuery( '#{$key}' ).val( attachment.sizes.thumbnail.url );
						jQuery( '#{$key}' ).val( attachment.url );
					} );
				 
					file_frame.open();
				} );
			} );
			</script>
		";
		
		$input = sprintf( '<input name="%s" type="text" id="%s" value="%s" title="%s" class="fp-image-upload-value %s">
							<input id="%s_button" type="button" value="%s" class="fp-image-upload-button">'
							, $key
							, $key
							, esc_attr( $value )
							, esc_attr( $value )
							, esc_attr( $type )
							, $key
							, $title
						);
		echo self::option_row( $key, $label, $input, $description );
	}
	
	private function image_input_old( $label, $key, $value, $description, $type ) {
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
		
		$input = sprintf( '<input name="%s" type="text" id="%s" value="%s" title="%s" class="fp-image-upload-value %s">
							<input id="%s_button" type="button" value="%s" class="fp-image-upload-button">'
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
	
	public function image_input( $label, $key, $value, $description = '', $type = 'regular-text' ) {
		if ( FOOTBALLPOOL_WP_MEDIA ) {
			self::image_input_WP3_5( $label, $key, $value, $description, $type );
		} else {
			self::image_input_old( $label, $key, $value, $description, $type );
		}
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
			$output .= sprintf( '<option id="%s_answer_%d" value="%s" %s %s>%s</option>'
								, esc_attr( $key )
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
								value="%s" %s %s> %s</label>'
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
	
	public function hidden_input( $key, $value, $return = 'echo' ) {
		$output = sprintf( '<input type="hidden" name="%s" id="%s" value="%s">'
						, esc_attr( $key )
						, esc_attr( $key )
						, esc_attr( $value )
					);
		
		if ( $return == 'echo' ) {
			echo $output;
		} else {
			return $output;
		}
	}
	
	public function no_input( $label, $value, $description ) {
		echo '<tr valign="top">
			<th scope="row"><label>', $label, '</label></th>
			<td>', $value, '</td>
			<td><span class="description">', $description, '</span></td>
			</tr>';
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
			if ( is_object( $value ) ) {
				$date = $value;
			} else {
				//$date = DateTime::createFromFormat( 'Y-m-d H:i', $value );
				$date = new DateTime( Football_Pool_Utils::date_from_gmt ( $value ) );
			}
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
									, __( 'For more information:', FOOTBALLPOOL_TEXT_DOMAIN )
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
		
		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( array( 'action', 'item_id' ), $current_url );
		printf( '<form action="%s" method="post">', $current_url );
		echo '<input type="hidden" name="action" id="action" value="update" />';
		wp_nonce_field( FOOTBALLPOOL_NONCE_ADMIN );
	}
	
	public function admin_footer() {
		echo '</form></div>';
	}
	
	public function bulk_actions( $actions, $name = 'action', $pagination = false ) {
		echo '<div class="tablenav top">';
		if ( count($actions) > 0 ) {
			echo '<div class="alignleft actions"><select id="', $name, '" name="', $name, '">';
			echo '<option selected="selected" value="-1">Bulk Actions</option>';
			foreach ( $actions as $action ) {
				printf( '<option value="%s" bulk-msg="%s">%s</option>'
						, $action[0]
						, ( isset( $action[2] ) ? $action[2] : '' )
						, $action[1]
				);
			}
			echo "</select><input onclick=\"return bulk_action_warning( '{$name}' )\" type='submit' value='Apply' class='button-secondary action' id='do{$name}' name='' />";
			echo '</div>';
		}
		
		if ( is_object( $pagination ) ) {
			$pagination->show();
		}
		echo '<br class="clear"></div>';
	}
	
	protected function list_table( $cols, $rows, $bulkactions = array(), $rowactions = array(), $pagination = false ) {
		self::bulk_actions( $bulkactions, 'action', $pagination );
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
			echo '<th id="', esc_attr( $col[2] ), '-', $tag, '" class="manage-column column-', esc_attr( $col[2] ), '" scope="col">', $col[1], '</th>';
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
	
	public function empty_scorehistory( $ranking_id = 'all' ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		if ( $ranking_id == 'all' ) {
			// $sql = "SELECT id FROM {$prefix}rankings WHERE calculate = 0";
			// $do_not_delete_these = implode( ',', array_merge( $wpdb->get_col( $sql ), array( 0 ) ) );
			// $sql = "DELETE FROM {$prefix}scorehistory WHERE ranking_id NOT IN ( {$do_not_delete_these} )";
			// $check = ( $wpdb->query( $sql ) !== false );
			$check = self::empty_table( 'scorehistory' );
		} elseif ( $ranking_id == 'smart set' ) {
			$sql = "SELECT DISTINCT( ranking_id ) FROM {$prefix}rankings_updatelog WHERE is_single_calculation = 0";
			$delete_these = implode( 
								',', 
								array_merge( $wpdb->get_col( $sql ), array( FOOTBALLPOOL_RANKING_DEFAULT ) ) 
							);
			$sql = "SELECT DISTINCT( rl.ranking_id ) FROM {$prefix}rankings_updatelog rl
					JOIN {$prefix}rankings r ON ( r.id = rl.ranking_id ) 
					WHERE rl.is_single_calculation = 1 OR r.calculate = 0";
			$do_not_delete_these = implode( ',', array_merge( $wpdb->get_col( $sql ), array( 0 ) ) );
			$sql = "DELETE FROM {$prefix}scorehistory 
					WHERE ranking_id IN ( {$delete_these} ) AND ranking_id NOT IN ( {$do_not_delete_these} )";
			$check = ( $wpdb->query( $sql ) !== false );
		} elseif ( is_int( $ranking_id ) && $ranking_id > 0 ) {
			$sql = $wpdb->prepare( "DELETE FROM {$prefix}scorehistory WHERE ranking_id = %d", $ranking_id );
			$check = ( $wpdb->query( $sql ) !== false );
		} else {
			$check = false;
		}
		
		return $check;
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
	
	private function recalculate_scorehistory_lightbox( $ranking_id = 0 ) {
		$single_ranking = ( $ranking_id > 0 ) ? "0, {$ranking_id}" : '';
		echo "<script> calculate_score_history({$single_ranking}) </script>";
	}
	
	public function recalculate_button( $ranking_id = 0 ) {
		if ( FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX ) {
			$nonce = wp_create_nonce( FOOTBALLPOOL_NONCE_SCORE_CALC );
			self::secondary_button( 
						__( 'Recalculate Scores', FOOTBALLPOOL_TEXT_DOMAIN )
						, "admin.php?page=footballpool-score-calculation&fp_recalc_nonce={$nonce}"
						, false
						, 'link'
					);
		} else {
			$single_ranking = ( $ranking_id > 0 ) ? "0, {$ranking_id}" : '';
			
			self::secondary_button( __( 'Recalculate Scores', FOOTBALLPOOL_TEXT_DOMAIN )
									, array( '', "calculate_score_history({$single_ranking})" )
									, false
									, 'js-button' 
			);
		}
	}
	
	public function update_score_history( $force = 'no', $ranking_id = 0 ) {
		$auto_calc = Football_Pool_Utils::get_fp_option( 'auto_calculation'
														, FOOTBALLPOOL_RANKING_AUTOCALCULATION
														, 'int' );
		
		if ( ! FOOTBALLPOOL_RANKING_CALCULATION_NOAJAX && ( $auto_calc == 1 || $force == 'force' ) ) {
			self::recalculate_scorehistory_lightbox( $ranking_id );
		} else {
			self::recalculate_button( $ranking_id );
		}
		return true;
	}
	
	public function update_ranking_log( $ranking_id, $old_set, $new_set, $log_message
										, $preserve_keys = 'no', $is_single_calculation = 0 ) {
		if ( $new_set == null || $old_set == null ) {
			$log = true;
		} elseif ( is_array( $new_set ) && is_array( $old_set ) ) {
			$log = ( count( $new_set ) != count( $old_set ) );
			if ( $preserve_keys == 'assoc' || $preserve_keys == 'preserve keys' ) {
				$log = ( $log || count( array_diff_assoc( $new_set, $old_set ) ) > 0 );
			} else {
				$log = ( $log || count( array_diff( $new_set, $old_set ) ) > 0 );
			}
		} else {
			$log = ( $old_set != $new_set );
		}
		
		if ( $log ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			
			$sql = $wpdb->prepare( "INSERT INTO {$prefix}rankings_updatelog 
										( ranking_id, log_message, log_date, is_single_calculation )
									VALUES ( %d, %s, NOW(), %d )"
									, $ranking_id, $log_message, $is_single_calculation
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
	public function link_button( $text, $action, $wrap = false, $other_attributes = null, $type = 'secondary' ) {
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
		
		if ( $action_val != '' ) $action_val = "location.href='{$action_val}';";
		$button = sprintf( '<input type="button" onclick="%s%s" 
									class="button button-%s" value="%s" %s/>'
							, $action_val
							, $onclick_val
							, $type
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
	
	public function example_date( $gmt = 'false', $offset = -1 ) {
		if ( $offset == -1 ) $offset = 14 * 24 * 60 * 60;
		$date = date( 'Y-m-d 18:00', time() + $offset );
		if ( $gmt == 'gmt' ) $date = Football_Pool_Utils::gmt_from_date( $date );
		return $date;
	}
}
