<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Googlemap Class
 *
 * @package     ExpressionEngine
 * @category    Plugin
 * @author      Ciprian Popescu
 * @copyright   Copyright (c) 2013, Ciprian Popescu
 * @link        http://getbutterfly.com/expressionengine/
 */

$plugin_info = array(
    'pi_name'         => 'Google Map',
    'pi_version'      => '0.6',
    'pi_author'       => 'Ciprian Popescu',
    'pi_author_url'   => 'http://getbutterfly.com/expressionengine/',
    'pi_description'  => 'This plugin outputs a full width Google Maps based on tag coordinates or location.',
    'pi_usage'        => Googlemap::usage()
);

class Googlemap {
    public $return_data = '';

    public function __construct() {
        $zoom = ee()->TMPL->fetch_param('zoom');
        $container = ee()->TMPL->fetch_param('container');

        $width = ee()->TMPL->fetch_param('width');
        $height = ee()->TMPL->fetch_param('height');
        $location = ee()->TMPL->fetch_param('location');

        $streetview = ee()->TMPL->fetch_param('streetview');

        $lat = ee()->TMPL->fetch_param('lat');
        $lon = ee()->TMPL->fetch_param('lon');

        if(!is_numeric($lat)) $lat = 0;
        if(!is_numeric($lon)) $lon = 0;
        if(empty($width)) $width = '100%';
        if(empty($height)) $height = '400px';

        $map_coordinates = ee()->TMPL->tagdata;
        
//      $plugin_path = PATH_THIRD; // not working with local ports
        $plugin_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', PATH_THIRD);
		$out = '<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>';

        $this->return_data .= $out;
        $this->return_data .= Googlemap::roo_maps($lat, $lon, "$container", $zoom, $width, $height, 'ROADMAP', "$location", "yes", $plugin_path . 'googlemap/images/marker.png', 'no', '', $streetview);
    }

    public static function usage() {
        ob_start();  ?>
{exp:googlemap zoom="17" container="map1" width="60%" height="400px" location="New York, USA" streetview="false"}

or

{exp:googlemap zoom="17" container="map1" width="60%" height="400px" lat="53.3218692" lon="-6.2645621" streetview="true"}

The container should have a different name for each embedded map.
Width and height allow for both percentage and pixel dimensions (note that 'em' is also available).
The streetview parameter is mandatory.
A map tag should have either a location parameter or a lat/lon combination, not both.
    <?php
        $buffer = ob_get_contents();
        ob_end_clean();

        return $buffer;
    }

	public static function roo_maps($lat, $lon, $id, $z, $w, $h, $maptype, $address, $marker, $markerimage, $traffic, $infowindow, $streetview) {
		/*
		'lat' 			=> 0,
		'lon' 			=> 0,
		'id' 			=> 'map',
		'z' 			=> 8,
		'w' 			=> 400,
		'h' 			=> 300,
		'maptype' 		=> 'TERRAIN',
		'address' 		=> '',
		'marker' 		=> '',
		'markerimage' 	=> '',
		'traffic' 		=> 'no',
		'infowindow' 	=> '',
        'streetview'    => 'true'
		*/

		$returnme = '';

		$returnme .= '
			<div id="' . $id . '" style="width: ' . $w . '; height: ' . $h . '; -webkit-box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);"></div>
			<script>
            google.maps.visualRefresh = true;

			var latlng = new google.maps.LatLng(' . $lat . ', ' . $lon . ');
			var myOptions = {
				zoom: ' . $z . ',
				center: latlng,
				mapTypeId: google.maps.MapTypeId.' . $maptype . ',
				streetViewControl: ' . $streetview . '
			};
			var ' . $id . ' = new google.maps.Map(document.getElementById("' . $id . '"), myOptions);';

			//traffic
			if($traffic == 'yes') {
				$returnme .= '
				var trafficLayer = new google.maps.TrafficLayer();
				trafficLayer.setMap(' . $id . ');';
			}

			//address
			if($address != '') {
				$returnme .= '
				var geocoder_' . $id . ' = new google.maps.Geocoder();
				var address = \'' . $address . '\';
				geocoder_' . $id . '.geocode({ \'address\': address}, function(results, status) {
					if(status == google.maps.GeocoderStatus.OK) {
						' . $id . '.setCenter(results[0].geometry.location);';

						if($marker !='') {
							//add custom image
							if($markerimage !='') {
								$returnme .= 'var image = "' . $markerimage . '";';
							}
							$returnme .= '
							var marker = new google.maps.Marker({
								map: ' . $id . ', ';
								if($markerimage !='')
									$returnme .= 'icon: image,';
							$returnme .= '
								position: ' . $id . '.getCenter()
							});';

							//infowindow
							if($infowindow != '') {
								//first convert and decode html chars
								$thiscontent = htmlspecialchars_decode($infowindow);
								$returnme .= '
								var contentString = \'' . $thiscontent . '\';
								var infowindow = new google.maps.InfoWindow({
									content: contentString
								});
											
								google.maps.event.addListener(marker, \'click\', function() {
								  infowindow.open(' . $id . ',marker);
								});';
							}
						}
				$returnme .= '
					}
				});';
			}

			// marker: show if address is not specified
			if($marker != '' && $address == '') {
				// add custom image
				if($markerimage != '')
					$returnme .= 'var image = "' . $markerimage . '";';

				$returnme .= '
					var marker = new google.maps.Marker({
					map: ' . $id . ', ';
					if($markerimage != '')
						$returnme .= 'icon: image,';

				$returnme .= '
					position: ' . $id . '.getCenter()
				});';

				//infowindow
				if($infowindow != '') {
					$returnme .= '
					var contentString = \'' . $infowindow . '\';

					var infowindow = new google.maps.InfoWindow({
						content: contentString
					});
								
					google.maps.event.addListener(marker, \'click\', function() {
					  infowindow.open(' . $id . ',marker);
					});';
				}
			}

		$returnme .= '</script>';
		return $returnme;
	}

    // END
}
/* End of file pi.googlemap.php */
/* Location: ./system/expressionengine/third_party/googlemap/pi.googlemap.php */