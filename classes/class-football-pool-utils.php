<?php
class Football_Pool_Utils {
	
	public function full_url() {
		// http://snipplr.com/view.php?codeview&id=2734
		$s = empty( $_SERVER["HTTPS"] ) ? '' : ( $_SERVER["HTTPS"] == "on" ) ? "s" : "";
		$protocol = substr( strtolower( $_SERVER["SERVER_PROTOCOL"] ), 0, strpos( strtolower( $_SERVER["SERVER_PROTOCOL"] ), "/" ) ) . $s;
		$port = ( $_SERVER["SERVER_PORT"] == "80" ) ? "" : ( ":" . $_SERVER["SERVER_PORT"] );
		return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
	}
	
	public function get_wp_option( $key, $default, $type = 'text' ) {
		$value = get_option( $key, $default );
		if ( $type == 'int' || $type == 'integer' ) {
			if ( ! is_numeric( $value ) ) $value = $default;
		}
		return $value;
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
		return ( isset( $_GET[$key] ) ? stripslashes( $_GET[$key] ) : $default );
	}
	public function post_str( $key, $default = '' ) {
		return self::post_string( $key, $default );
	}
	public function post_string( $key, $default = '' ) {
		return ( isset( $_POST[$key] ) ? stripslashes( $_POST[$key] ) : $default );
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
		return ( isset( $_GET[$key] ) && is_array( $_GET[$key] ) ? stripslashes_deep( $_GET[$key] ) : $default );
	}
	public function post_str_array( $key, $default = array() ) {
		return self::post_string_array( $key, $default );
	}
	public function post_string_array( $key, $default = array() ) {
		return ( isset( $_POST[$key] ) && is_array( $_POST[$key] ) ? stripslashes_deep( $_POST[$key] ) : $default );
	}
	
	// print information about a variable in a human readable way
	public function debug( $var, $type='echo' ) {
		$pre  = "<pre style='border: 1px solid;'>";
		$pre .= "<div style='padding:2px;color:#fff;background-color:#000;'>debug</div><div style='padding:2px;'>";
		$post = "</div></pre>";
		switch ( $type ) {
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
			case 'return':
				return $pre . var_export( $var, true ) . $post;
				break;
			case 'echo':
			default:
				echo $pre;
				var_dump( $var );
				echo $post;
		}
	}

}
?>