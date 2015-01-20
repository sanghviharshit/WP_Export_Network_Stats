<?php


// Add IE Fallback for HTML5 and canvas
// - - - - - - - - - - - - - - - - - - - - - - -
function ens_charts_html5_support () {
    echo '<!--[if lte IE 8]>';
    echo '<script src="'.plugins_url( '/js/excanvas.compiled.js', __FILE__ ).'"></script>';
    echo '<![endif]-->';
    echo '	<style>
    			/*ens_charts_js responsive canvas CSS override*/
    			.ens_charts_canvas {
    				width:100%!important;
    				max-width:100%;
    			}

    			@media screen and (max-width:480px) {
    				div.wp-chart-wrap {
    					width:100%!important;
    					float: none!important;
						margin-left: auto!important;
						margin-right: auto!important;
						text-align: center;
    				}
    			}
    		</style>';
}

// Register Script
// - - - - - - - - - - - - - - - - - - - - - - -
function ens_charts_load_scripts() {

	if ( is_Admin() ) {
		// WP Scripts
		wp_enqueue_script( 'jquery' );

		// Register plugin Scripts
		wp_register_script( 'charts-js', ENS_PLUGIN_URL.'js/Chart.min.js');
		wp_register_script( 'wp-chart-functions', ENS_PLUGIN_URL.'js/functions.js');

		// Enqeue those suckers
		wp_enqueue_script( 'charts-js' );
		wp_enqueue_script( 'wp-chart-functions' );
	}

}

// make sure there are the right number of colors in the colour array
// - - - - - - - - - - - - - - - - - - - - - - -
if ( !function_exists('ens_charts_compare_fill') ) {
	function ens_charts_compare_fill(&$measure,&$fill) {
		// only if the two arrays don't hold the same number of elements
		if (count($measure) != count($fill)) {
		    // handle if $fill is less than $measure
		    while (count($fill) < count($measure) ) {
		        $fill = array_merge( $fill, array_values($fill) );
		    }
		    // handle if $fill has more than $measure
		    $fill = array_slice($fill, 0, count($measure));
		}
	}
}

// color conversion function
// - - - - - - - - - - - - - - - - - - - - - - -
if (!function_exists( "ens_charts_hex2rgb" )) {
	function ens_charts_hex2rgb($hex) {
	   $hex = str_replace("#", "", $hex);

	   if(strlen($hex) == 3) {
	      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
	      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
	      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
	   } else {
	      $r = hexdec(substr($hex,0,2));
	      $g = hexdec(substr($hex,2,2));
	      $b = hexdec(substr($hex,4,2));
	   }

	   $rgb = array($r, $g, $b);
	   return implode(",", $rgb); // returns the rgb values separated by commas
	}
}

// ens_charts_trailing_comma
// - - - - - - - - - - - - - - - - - - - - - - -
if (!function_exists('ens_charts_trailing_comma')) {
	function ens_charts_trailing_comma($incrementor, $count, &$subject) {
		$stopper = $count - 1;
		if ($incrementor !== $stopper) {
			return $subject .= ',';
		}
	}
}





// Chart Shortcode 1 - Core Shortcode with all options
// - - - - - - - - - - - - - - - - - - - - - - -
function ens_charts_shortcode( $atts) {

	// Default Attributes
	// - - - - - - - - - - - - - - - - - - - - - - -
	extract( shortcode_atts(
		array(
			'type'             => 'pie',
			'title'            => 'chart',
			'canvaswidth'      => '600',
			'canvasheight'     => '600',
			'width'			   => '40%',
			'height'		   => 'auto',
			'margin'		   => '5px',
			'relativewidth'	   => '1',
			'align'            => '',
			'class'			   => '',
			'labels'           => 'A,B,C',
			'data'             => '30,50,100',
			'datasets'         => '30,50,100 next 20,90,75',
			'colors'           => '#69D2E7,#E0E4CC,#F38630,#96CE7F,#CEBC17,#CE4264',
			'fillopacity'      => '0.7',
			'pointstrokecolor' => '#FFFFFF',
			'animation'		   => 'true',
			'scalefontsize'    => '12',
			'scalefontcolor'   => '#666',
			'scaleoverride'    => 'false',
			'scalesteps' 	   => 'null',
			'scalestepwidth'   => 'null',
			'scalestartvalue'  => 'null'
		), 
		/*array( 'title'=>'mypie', 'type'=>'pie', 'align'=>'alignright', 'margin'=>'5px 20px', 'data'=>'10,32,50,25,5' )*/
		$atts 
		)
	);

	// prepare data
	// - - - - - - - - - - - - - - - - - - - - - - -
	$title    = str_replace(' ', '', $title);
	$data     = explode(',', str_replace(' ', '', $data));
	$labels     = explode(',', str_replace(' ', '', $labels));
	$datasets = explode("next", str_replace(' ', '', $datasets));
	// check that the colors are not an empty string
	if ($colors != "") {
		$colors   = explode(',', str_replace(' ','',$colors));
	} else {
		$colors = array('#69D2E7','#E0E4CC','#F38630','#96CE7F','#CEBC17','#CE4264');
	}

	(strpos($type, 'lar') !== false ) ? $type = 'PolarArea' : $type = ucwords($type);

	// output - covers Pie, Doughnut, and PolarArea
	// - - - - - - - - - - - - - - - - - - - - - - -
	$currentchart = '<div class="'.$align.' '.$class.' wp-chart-wrap" style="max-width: 100%; width:'.$width.'; height:'.$height.';margin:'.$margin.';" data-proportion="'.$relativewidth.'">';
	$currentchart .= '<canvas id="'.$title.'" height="'.$canvasheight.'" width="'.$canvaswidth.'" class="ens_charts_canvas" data-proportion="'.$relativewidth.'"></canvas></div>
	<script>';

		// output Options
	$currentchart .= 'var '.$title.'Ops = {
		animation: '.$animation.',';

	if ($type == 'Line' || $type == 'Radar' || $type == 'Bar' || $type == 'PolarArea') {
		$currentchart .=	'scaleFontSize: '.$scalefontsize.',';
		$currentchart .=	'scaleFontColor: "'.$scalefontcolor.'",';
		$currentchart .=    'scaleOverride:'   .$scaleoverride.',';
		$currentchart .=    'scaleSteps:' 	   .$scalesteps.',';
		$currentchart .=    'scaleStepWidth:'  .$scalestepwidth.',';
		$currentchart .=    'scaleStartValue:' .$scalestartvalue;
	}

	// end options array
	$currentchart .= '}; ';

	// start the js arrays correctly depending on type
	if ($type == 'Line' || $type == 'Radar' || $type == 'Bar' ) {

		ens_charts_compare_fill($datasets, $colors);
		$total    = count($datasets);

		// output labels
		$currentchart .= 'var '.$title.'Data = {';
		$currentchart .= 'labels : [';
		$labelstrings = explode(',',$labels);
		for ($j = 0; $j < count($labelstrings); $j++ ) {
			$currentchart .= '"'.$labelstrings[$j].'"';
			ens_charts_trailing_comma($j, count($labelstrings), $currentchart);
		}
		$currentchart .= 	'],';
		$currentchart .= 'datasets : [';
	} else {
		ens_charts_compare_fill($data, $colors);
		$total = count($data);
		$currentchart .= 'var '.$title.'Data = [';
	}

		// create the javascript array of data and attr correctly depending on type
		for ($i = 0; $i < $total; $i++) {

			if ($type === 'Pie' || $type === 'Doughnut' || $type === 'PolarArea') {
				$currentchart .= '{
					value 	: '. $data[$i] .',
					color 	: '.'"'. $colors[$i].'"'.',
					label   : '. '"'. $labels[$i] .'"'.',
					labelFontSize : "16"
				}';

			} else if ($type === 'Bar') {
				$currentchart .= '{
					fillColor 	: "rgba('. ens_charts_hex2rgb( $colors[$i] ) .','.$fillopacity.')",
					strokeColor : "rgba('. ens_charts_hex2rgb( $colors[$i] ) .',1)",
					data 		: ['.$datasets[$i].']
				}';

			} else if ($type === 'Line' || $type === 'Radar') {
				$currentchart .= '{
					fillColor 	: "rgba('. ens_charts_hex2rgb( $colors[$i] ) .','.$fillopacity.')",
					strokeColor : "rgba('. ens_charts_hex2rgb( $colors[$i] ) .',1)",
					pointColor 	: "rgba('. ens_charts_hex2rgb( $colors[$i] ) .',1)",
					pointStrokeColor : "'.$pointstrokecolor.'",
					data 		: ['.$datasets[$i].']
				}';

			}  // end type conditional
			ens_charts_trailing_comma($i, $total, $currentchart);
		}

		// end the js arrays correctly depending on type
		if ($type == 'Line' || $type == 'Radar' || $type == 'Bar') {
			$currentchart .=	']};';
		} else {
			$currentchart .=	'];';
		}

		$currentchart .= 'var wpChart'.$title.$type.' = new Chart(document.getElementById("'.$title.'").getContext("2d")).'.$type.'('.$title.'Data,'.$title.'Ops);
	</script>';

	// return the final result
	// - - - - - - - - - - - - - - - - - - - - - - -
	echo '<br/><br/>.'.$currentchart.'.';
}




