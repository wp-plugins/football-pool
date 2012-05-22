<?php
// Based on Highcharts
class Football_Pool_Chart {
	public $ID;
	public $type; // pie, line, etc.
	public $data = array();
	public $width;
	public $height;
	public $title = '';
	public $options = array();
	public $JS_options = array(); // use these to extend default options, or overwrite values (use JS dot-notation)
	public $custom_css = '';
	public $css_class = 'chart';
	public $x_axis_step = 1;
	
	public function __construct( $id = 'chart1', $type = 'line', $width = 600, $height = 400 ) {
		$this->ID = $id;
		$this->width = $width;
		$this->height = $height;
		$this->type = $type;
	}
		
	public function draw() {
		$output = '';
		
		if ( $this->custom_css != '' ) 
			$this->css_class .= ' ' . $this->custom_css;
		
		$output .= $this->render_base_HTML();
		switch ( $this->type ) {
			case 'line':
				$this->line_definition();
				break;
			case 'bar':
				// not yet implemented
				break;
			case 'column':
				$this->column_definition();
				break;
			case 'pie':
				$this->pie_definition();
				break;
			default:
				break;
		}

		$output .= $this->render_options( $this->options );
		$output .= $this->finish_chart();
		
		return $output;
	}
	
	private function render_base_HTML() {
		$output = sprintf( '<div id="%s" class="%s" style="width:%dpx; height:%dpx;"></div>',
							$this->ID, $this->css_class, $this->width, $this->height );
		$output .= sprintf( "<script type='text/javascript'>
							var chart_%s;
							jQuery(document).ready(function() {
								var options = {
									chart: {
										renderTo: '%s',
										plotBackgroundColor: null,
										plotBorderWidth: 1,
										plotShadow: false
									}
									,title: {
										text: '%s'
									}
							",
							$this->ID, $this->ID, $this->title
						);
		return $output;
	}
	
	private function finish_chart() {
		$output = "				};";
					
		$output .= $this->render_JS_options();
		
		$output .= sprintf( "chart_%s = new Highcharts.Chart(options);
							});
						</script>", $this->ID);
		return $output;
	}
	
	private function series_data_template( $name, $data = array(), $type = '', $options = array() ) {
		$output = "{ name: '" . $name . "', data: " . json_encode($data);
		if ( count( $options ) > 0 )
			$output .= implode(", ", $options) . ", ";
		
		if ( $type != '' )
			$output .= ", type: '" . $type . "'"; 
		
		$output .= " }";
		
		return $output;
	}
	
	private function render_JS_options() {
		$output = '';
		if ( count( $this->JS_options ) > 0 ) {
			$output .= implode( ";", $this->JS_options );
			$output .= ";";
		}
		return $output;
	}
	
	private function render_options() {
		$output = '';
		if ( count( $this->options ) > 0 ) {
			$output .= ",";
			$output .= implode( ",", $this->options );
		}
		return $output;
	}
	
	private function column_definition() {
		$this->options[] = "plotOptions: {
								series: {
									minPointLength: 3
								}
							}";
		$this->options[] = "yAxis: {
								title: { text: null }, 
								showFirstLabel: true, 
								startOnTick: true,
								allowDecimals: false
							}";
		$this->JS_options[] = "options.chart.defaultSeriesType = 'column'";
		$series = array();
		foreach ( $this->data as $user => $serie ) {
			$series[] = $this->series_data_template( $user, $serie );
		}
		$this->options[] = "series: [" . implode( ',', $series ) . "]";
	}
	
	private function pie_definition() {
		$this->options[] = "tooltip: {
								formatter: function() {
									//return '<b>'+ this.point.name +'</b>: '+ sprintf('%1.0f', this.percentage) +' %';
									return '<b>'+ this.point.name +'</b>: '+ this.y + ' (' + this.percentage.toFixed(0) +' %)';
								}
							}";
		$this->options[] = "plotOptions: {
								pie: {
									allowPointSelect: true,
									cursor: 'pointer',
									dataLabels: {
										enabled: false
									},
									showInLegend: true
								}
							}";
		$this->options[] = "series: [" . $this->series_data_template('scores', $this->data, 'pie') . "]";
	}
	
	private function line_definition() {
		$this->options[] = "plotOptions: {
								series: {
									marker: {
										enabled: false,
										states: {
											hover: {
												enabled: true
											}
										}
									}
								}
							}";
		$this->options[] = sprintf( "yAxis: {
										title: { text: '%s' }, 
										min: 0, 
										showFirstLabel: true, 
										startOnTick: false,
										allowDecimals: false
									}"
									, __( 'punten', FOOTBALLPOOL_TEXT_DOMAIN )
							);
		$this->options[] = sprintf( "xAxis: { 
										allowDecimals: false,
										title: { text: '%s' }, 
										labels: { 
											enabled: true
											//,rotation: -45
											//,align: 'right'
										}
									}"
									, __( 'wedstrijdverloop', FOOTBALLPOOL_TEXT_DOMAIN ) 
							);
		$this->options[] = sprintf( "subtitle: { 
										text: document.ontouchstart === undefined ?
											'%s' :
											'%s' }"
									, __( 'Klik en sleep in de grafiek om in te zoomen', FOOTBALLPOOL_TEXT_DOMAIN )
									, __( 'Sleep je vinger over de grafiek om in te zoomen', FOOTBALLPOOL_TEXT_DOMAIN )
							);
		$this->JS_options[] = "options.chart.zoomType = 'x'";
		
		$onepoint = false;
		$series = array();
		foreach( $this->data['series'] as $serie ) {
			if ( ! $onepoint && count( $serie['data'] ) == 1 ) {
				$onepoint = true;
				$this->JS_options[] = "options.plotOptions.series.marker.enabled = true";
				$this->JS_options[] = "options.plotOptions.series.marker.symbol = 'circle'";
			}
			$series[] = $this->series_data_template( $serie['name'], $serie['data'] );
		}
		$this->JS_options[] = "var categories = " . json_encode( $this->data['categories'] );
		$this->options[] = "series: [" . implode(',', $series) . "]";
	}

	public function remove_last_point_from_series() {
		return sprintf( "<script type='text/javascript'>
							jQuery(document).ready(function() {
								jQuery.each(chart_%s.series, function() { this.data[this.data.length-1].remove() } );
							});
						</script>", 
					$this->ID
				);
	}
}
?>