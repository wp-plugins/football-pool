<?php
class Football_Pool_Utils {
	// extract ids from a string ("x", "x-z", "x,y,z").
	// only integers are returned
	public function extract_ids( $input ) {
		// remove all spaces and tabs
		$replace = array( ' ', "\t" );
		$replace_with = array( '', '' );
		$input = str_replace( $replace, $replace_with, $input );
		$ids = array();
		// split for single numbers
		$input = explode( ',', $input );
		foreach ( $input as $part ) {
			// split in case of ranges
			$range = explode( '-', $part );
			if ( count( $range ) == 2 ) {
				// a range: lower-upper
				$lower = (int) $range[0];
				$upper = (int) $range[1];
				// always include lower limit
				$ids[] = $lower++;
				while ( $lower <= $upper ) {
					$ids[] = $lower++;
				}
			} else {
				// single number
				$ids[] = (int) $range[0];
			}
			// other cases are ignored, e.g. x--y
		}
		
		return $ids;
	}
	
	// returns an int and stores the value+1 in the WP cache
	public function get_counter_value( $cache_key ) {
		$id = wp_cache_get( $cache_key );
		if ( $id === false ) {
			$id = 1;
		}
		wp_cache_set( $cache_key, $id + 1 );
		
		return $id;
	}
	
	// accepts a date in Y-m-d H:i format and changes it to UTC
	public function gmt_from_date( $date_string ) {
		if ( strlen( $date_string ) == strlen( '0000-00-00 00:00' ) ) $date_string .= ':00';
		return $date_string != '' ? get_gmt_from_date( $date_string, 'Y-m-d H:i' ) : '';
	}
	
	// accepts a date in Y-m-d H:i format and changes it to local time according to WP's timezone setting
	public function date_from_gmt( $date_string ) {
		if ( strlen( $date_string ) == strlen( '0000-00-00 00:00' ) ) $date_string .= ':00';
		return $date_string != '' ? get_date_from_gmt( $date_string, 'Y-m-d H:i' ) : '';
	}
	
	public function full_url() {
		// http://snipplr.com/view.php?codeview&id=2734
		$s = empty( $_SERVER["HTTPS"] ) ? '' : ( $_SERVER["HTTPS"] == "on" ) ? "s" : "";
		$protocol = substr( strtolower( $_SERVER["SERVER_PROTOCOL"] ), 0, strpos( strtolower( $_SERVER["SERVER_PROTOCOL"] ), "/" ) ) . $s;
		$port = ( $_SERVER["SERVER_PORT"] == "80" ) ? "" : ( ":" . $_SERVER["SERVER_PORT"] );
		return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
	}
	
	public function set_fp_option( $key, $value ) {
		self::update_fp_option( $key, $value );
	}
	
	public function update_fp_option( $key, $value, $overwrite = 'overwrite' ) {
		$options = get_option( FOOTBALLPOOL_OPTIONS, array() );
		if ( ! isset( $options[$key] ) || ( isset( $options[$key] ) && $overwrite == 'overwrite' ) ) {
			$options[$key] = $value;
			update_option( FOOTBALLPOOL_OPTIONS, $options );
		}
	}
	
	public function get_fp_option( $key, $default = '', $type = 'text' ) {
		$options = get_option( FOOTBALLPOOL_OPTIONS, array() );
		$value = isset( $options[$key] ) ? $options[$key] : $default;
		if ( $type == 'int' || $type == 'integer' ) {
			if ( ! is_numeric( $value ) ) $value = $default;
		}
		return $value;
	}
	
	// damn you, magic quotes!
	// and damn you, WP, for not telling me about wp_magic_quotes()!
	// http://kovshenin.com/2010/wordpress-and-magic-quotes/
	public function safe_stripslashes( $value ) {
		// return get_magic_quotes_gpc() ? stripslashes( $value ) : $value;
		if ( is_array( $value) )
			return stripslashes_deep( $value );
		else
			return stripslashes( $value );
	}
	public function safe_stripslashes_deep( $value ) {
		// return get_magic_quotes_gpc() ? stripslashes_deep( $value ) : $value;
		return stripslashes_deep( $value );
	}
	
	// String GET and POST
	public function request_str( $key, $default = '' ) {
		return self::request_string( $key, $default );
	}
	public function request_string( $key, $default = '' ) {
		return ( $_POST ? self::post_string( $key, $default ) : self::get_string( $key, $default ) );
	}
	public function get_str( $key, $default = '' ) {
		return self::get_string( $key, $default );
	}
	public function get_string( $key, $default = '' ) {
		return ( isset( $_GET[$key] ) ? self::safe_stripslashes( $_GET[$key] ) : $default );
	}
	public function post_str( $key, $default = '' ) {
		return self::post_string( $key, $default );
	}
	public function post_string( $key, $default = '' ) {
		return ( isset( $_POST[$key] ) ? self::safe_stripslashes( $_POST[$key] ) : $default );
	}
	
	// Integer GET and POST
	public function request_int( $key, $default = 0 ) {
		return self::request_integer( $key, $default );
	}
	public function request_integer($key, $default = 0)
	{
		return ($_POST ? self::post_integer($key, $default) : self::get_integer($key, $default));
	}
	public function get_integer( $key, $default = 0 ) {
		return ( isset( $_GET[$key] ) && is_numeric( $_GET[$key] )? (integer) $_GET[$key] : $default );
	}
	public function get_int( $key, $default = 0 ) {
		return self::get_integer( $key, $default );
	}
	public function post_integer( $key, $default = 0 ) {
		return ( isset( $_POST[$key] ) && is_numeric( $_POST[$key] )? (integer) $_POST[$key] : $default );
	}
	public function post_int( $key, $default = 0 ) {
		return self::post_integer( $key, $default );
	}
	
	// Array of integers GET and POST
	public function request_int_array( $key, $default = array() ) {
		return self::request_integer_array( $key, $default );
	}
	public function request_integer_array( $key, $default = array() ) {
		if ( $_POST ? self::post_integer_array( $key, $default ) : self::get_integer_array( $key, $default ) );
	}
	public function get_intArray( $key, $default = array() ) {
		return self::get_integer_array( $key, $default );
	}
	public function get_integer_array( $key, $default = array() ) {
		if ( isset( $_GET[$key] ) && is_array( $_GET[$key] ) ) {
			$get = $_GET[$key];
			foreach ( $get as $str ) $arr[] = (integer) $str;
		} else {
			$arr = $default;
		}
		
		return $arr;
	}
	public function post_int_array( $key, $default = array() ) {
		return self::post_integer_array( $key, $default );
	}
	public function post_integer_array( $key, $default = array() ) {
		if ( isset( $_POST[$key] ) && is_array( $_POST[$key] ) ) {
			$post = $_POST[$key];
			foreach ( $post as $str ) $arr[] = (integer) $str;
		} else {
			$arr = $default;
		}
		
		return $arr;
	}
	
	// Array of stings GET and POST
	public function request_str_array( $key, $default = array() ) {
		return self::request_string_array( $key, $default );
	}
	public function request_string_array( $key, $default = array() ) {
		return ( $_POST ? self::post_string_array( $key, $default ) : self::get_string_array( $key, $default ) );
	}
	public function get_str_array( $key, $default = array() ) {
		return self::get_string_array( $key, $default );
	}
	public function get_string_array( $key, $default = array() ) {
		return ( isset( $_GET[$key] ) && is_array( $_GET[$key] ) ? self::safe_stripslashes_deep( $_GET[$key] ) : $default );
	}
	public function post_str_array( $key, $default = array() ) {
		return self::post_string_array( $key, $default );
	}
	public function post_string_array( $key, $default = array() ) {
		return ( isset( $_POST[$key] ) && is_array( $_POST[$key] ) ? self::safe_stripslashes_deep( $_POST[$key] ) : $default );
	}
	
	// print information about a variable in a human readable way
	// if argument sleep is set, the execution will halt after the debug for the given amount of micro seconds
	// (one micro second = one millionth of a second)
	public function debug( $var, $type = 'echo', $sleep = 0 ) {
		if ( ! FOOTBALLPOOL_ENABLE_DEBUG ) return;
		
		$type = str_replace( array( 'only', 'just', ' ', '-' ), array( '', '', '', '' ), $type );
		
		if ( $type == 'once' || ( is_array( $type ) && $type[0] == 'once' ) ) {
			$type = isset( $type[1] ) ? $type[1] : 'echo';
			
			$cache_key = 'fp_debug';
			$i = wp_cache_get( $cache_key );
			if ( false === $i ) {
				$i = 1;
				wp_cache_set( $cache_key, $i );
			} else {
				$i++;
			}
			
			if ( $i > 1 ) return;
		}
		
		$pre  = "<pre style='border: 1px solid;'>";
		$pre .= "<div style='padding:2px;color:#fff;background-color:#000;'>debug</div><div style='padding:2px;'>";
		$post = "</div></pre>";
		switch ( $type ) {
			case 'log':
			case 'file':
				$pre  = "[" . date('D d/M/Y H:i P') . "]\n";
				$post = "\n-----------------------------------------------\n";
				if ( defined( 'FOOTBALLPOOL_ERROR_LOG' ) ) {
					if ( ! file_exists( FOOTBALLPOOL_ERROR_LOG ) ) {
						file_put_contents( FOOTBALLPOOL_ERROR_LOG, "{$pre}errorlog created{$post}" );
					}
					error_log( $pre . var_export( $var, true ) . $post, 3, FOOTBALLPOOL_ERROR_LOG );
				}
				break;
			case 'output':
			case 'return':
				return $pre . var_export( $var, true ) . $post;
				break;
			case 'echo':
			default:
				echo $pre;
				var_dump( $var );
				echo $post;
		}
		
		if ( is_int( $sleep ) && $sleep > 0 ) usleep( $sleep );
	}

}
?>