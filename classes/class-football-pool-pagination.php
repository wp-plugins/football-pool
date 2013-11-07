<?php
class Football_Pool_Pagination {
	public $show_total = true;
	public $page_param = 'paged';
	public $current_page = 1;
	public $wrap = false;
	
	private $total_pages = 0;
	private $total_items = 0;
	private $page_size = 20;
	
	public function __construct( $num_items, $wrap = false ) {
		$this->total_items = $num_items;
		$this->total_pages = $this->calc_total_pages( $num_items, $this->page_size );
		$this->current_page = $this->get_page_num();
		$this->wrap = $wrap;
	}
	
	public function get_page_size() {
		return $this->page_size;
	}
	public function set_page_size( $size ) {
		$this->page_size = $size;
		$this->total_pages = $this->calc_total_pages( $this->total_items, $this->page_size );
		$this->current_page = $this->get_page_num();
	}
	
	public function show( $return = 'echo' ) {
		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		
		if ( $this->total_pages ) {
			$page_class = $this->total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}
		
		$output = '';
		if ( $this->wrap ) $output .= sprintf( '<div class="tablenav top%s">', $page_class );
		
		$output .= sprintf( '<div class="tablenav-pages%s">', $page_class );
		if ( $this->show_total ) {
			$output .= sprintf( '<span class="displaying-num">%s</span>'
							, sprintf( _n( '1 item', '%s items', $this->total_items, FOOTBALLPOOL_TEXT_DOMAIN )
										, $this->total_items
								)
						);
		}
		
		$disable_first = $disable_last = '';
		if ( $this->current_page == 1 ) {
			$disable_first = ' disabled';
		}
		if ( $this->current_page == $this->total_pages ) {
			$disable_last = ' disabled';
		}
		
		$output .= '<span class="pagination-links">';
		$output .= sprintf( '<a class="first-page%s" title="%s" href="%s">&laquo;</a>'
							, $disable_first
							, esc_attr__( 'Go to the first page' )
							, esc_url( remove_query_arg( $this->page_param, $current_url ) )
					);
		$output .= sprintf( '<a class="prev-page%s" title="%s" href="%s">&lsaquo;</a>'
							, $disable_first
							, esc_attr__( 'Go to the previous page' )
							, esc_url( add_query_arg( 
											$this->page_param, max( 1, $this->current_page - 1 ), 
											$current_url ) )
					);
		
		$output .= sprintf( '<span class="paging-input"><input class="current-page" title="%s" type="text" name="%s" value="%d" size="%d"> of <span class="total-pages">%d</span></span>'
							, esc_attr__( 'Current page' )
							, $this->page_param
							, $this->current_page
							, strlen( $this->total_pages )
							, $this->total_pages
					);

		$output .= sprintf( '<a class="next-page%s" title="%s" href="%s">&rsaquo;</a>'
							, $disable_last
							, esc_attr__( 'Go to the next page' )
							, esc_url( add_query_arg( 
											$this->page_param, min( $this->total_pages, $this->current_page + 1 ), 
											$current_url ) )
					);
		$output .= sprintf( '<a class="last-page%s" title="%s" href="%s">&raquo;</a>'
							, $disable_last
							, esc_attr__( 'Go to the last page' )
							, esc_url( add_query_arg( $this->page_param, $this->total_pages, $current_url ) )
					);
		$output .= '</span></div>';
		
		if ( $this->wrap ) $output .= '</div>';
		
		if ( $return == 'echo' ) {
			echo $output;
		} else {
			return $output;
		}
	}
	
	private function calc_total_pages( $num_items, $page_size ) {
		return ceil( $num_items / $page_size );
	}
	
	private function get_page_num() {
		$page_num = Football_Pool_Utils::request_int( $this->page_param, 0 );

		if( $page_num > $this->total_pages ) {
			$page_num = $this->total_pages;
		}
		
		return max( 1, $page_num );
	}

}
