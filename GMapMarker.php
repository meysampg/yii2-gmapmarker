<?php

namespace meysampg\gmap;

use yii\base\Widget;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;

class GMapMarker extends Widget
{
	/**
	 * @var integer Counter of created marker element.
	 */
	public static $markerCount = 0;

	/**
	 * @var string The default character for generating the ID of map. For
	 * example it have been used on `<div id="m1"></div>`.
	 */
	public $prefix = 'm';

	/**
	 * @var intger Width of google map box in pixel. Use pure number for using
	 * %-based length.
	 */
	public $width = '600px';

	/**
	 * @var integer Height of google map box in pixel. Use pure number for using
	 * %-based length.
	 */
	public $height = '400px';

	/**
	 * @var integer Scale of showd region on google map box
	 */
	public $zoom = 4;

	/**
	 * @var array Array of points which must be showed on map as marker. It 
	 * can be in two format. The first is just create one marker on map:
	 * ```php
	 * $marks = [35.6892, 51.3890];
	 * ```
	 * The last is use `$marks` for showing multiple points:
	 * ```php
	 * $marks = [
	 * 		[35.6892, 51.3890],
	 * 		[31.3183, 48.6706],
	 * 		[29.4850, 57.6439]
	 * ];
	 * ```
	 * Also both of them can be on associative format (Of course for future
	 * development, It may be necessary to add label or other google map
	 * options to marker in this format), as an example for the former example,
	 * the associative format is:
	 * ```php
	 * $marks = [
	 * 		'latitude' => 35.6892,
	 * 		'longitude' => 51.3890
	 * ];
	 * ```
	 * On All above situations, the first element is `latitude` and the last
	 * is `longituide`.
	 */
	public $marks;

	/**
	 * @var array Latitude and longitude for setting as center position of map.
	 * Such as `$marks` this property, can be on two format:
	 * ```php
	 * $marks = [35.6892, 51.3890];
	 * ```
	 * and
	 * ```php
	 * $marks = [
	 * 		'latitude' => 35.6892,
	 * 		'longitude' => 51.3890
	 * ];
	 * ```
	 * If this varible doesn't initialized, `$marks` for single point and the
	 * first element of that for multidimensional array is picked.
	 */
	public $center;

	/**
	 * @var boolean Determine that default toolbar muse be showd or not.
	 */
	public $disableDefaultUI = false;

	private $_mapId;
	private $_markerId = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Care about the format of `$marks` array.
		if (!$this->isCorrectFormat($this->marks)) {
			throw new InvalidConfigException('$marks must be a non-empty array on correct format.');
		}

		// Check the initialized value of `$center`
		if (!$this->isCorrectFormat($this->center)) {
			if (ArrayHelper::isIndexed($this->marks, true)) {
				if (is_numeric($this->marks[0])) {
					$this->center = [$this->marks[0], $this->marks[1]];
				} elseif (ArrayHelper::isIndexed($this->marks[0], true)) {
					$this->center = [$this->marks[0][0], $this->marks[0][1]];
				} else {
					$this->center = [$this->marks[0]['latitude'], $this->marks[0]['longitude']];
				}
			} else {
				$this->center = [$this->marks['latitude'], $this->marks['longitude']];
			}
		}

		// Convert W/H to precent if need.
		if ((string)((int)$this->width) == $this->width) {
			$this->width .= '%';
		}
		if ((string)((int)$this->height) == $this->height) {
			$this->height .= '%';
		}

		// Convert to JS parsable expression
		$this->disableDefaultUI = (int)$this->disableDefaultUI;
	}

	/**
	 * @inheritdoc
	 */
	public function run()
	{
		GMapAsset::register($this->getView());
		$this->getView()->registerJs($this->initilizeJsFunction);
		$this->getView()->registerCss($this->initilizeCss);

		return "<div id='{$this->htmlIdOfMap()}'></div>";
	}

	public function getRenderMapMarkersPosition()
	{
		$strMarkersPosition = $this->createGJsPositionObject($this->center, 'c');

		$strMarkersPosition .= $this->generateMarkersJsCode($this->marks);

		return $strMarkersPosition;
	}

	public function getRenderMapMarkers()
	{
		$strMarkers = $this->createGJsMarkerObject($this->center, 'c');

		if (ArrayHelper::isIndexed($this->marks, true) && is_numeric($this->marks[0])) {
			$strMarkers .= $this->createGJsMarkerObject($this->marks);
		} else {
			foreach ($this->marks as $mark) {
				$strMarkers .= $this->createGJsMarkerObject($mark);
			}
		}

		return $strMarkers;
	}

	public function getRenderMap()
	{
		$str = $this->renderMapMarkersPosition
			 . $this->createGJsMapObejct()
			 . $this->renderMapMarkers;

		return $str;

	}

	public function getInitilizeJsFunction()
	{
		$initFunctionName = "initialize" . ucfirst($this->mapId);

		$str = "\nfunction $initFunctionName() {\n"
			 . "	{$this->renderMap}\n"
			 . "};\n";

		$str .= "google.maps.event.addDomListener(window, 'load', $initFunctionName);";

		return $str;
	}

	public function getInitilizeCss()
	{
		$str = "\n#{$this->htmlIdOfMap()} {\n"
			 . "	width: {$this->width};\n"
			 . "	height: {$this->height};\n"
			 . "}\n";

		return $str;
	}

	/**
	 * @return string unique ID of the map.
	 */
	public function getMapId()
	{
		if (null === $this->_mapId) {
			$this->_mapId = $this->prefix . static::$counter++;
		}

		return $this->_mapId;
	}

	/**
	 * Generate an ID for marker on a sequential order.
	 * 
	 * @param array $marker array of marker coordinates
	 * @param string $prefix prefix for prepend to marker id
	 * @return string An automatic generated ID (Like `1-1`) that the first
	 * element is the ID of map and the last is the id of marker.
	 */
	public function getMarkerId($marker, $prefix = null)
	{
		$marker = $this->pointToIndArray($marker);
		$key = substr(md5($marker[0] . $marker[1]), 0, 6);

		if (!isset($this->_markerId[$key])) {
			$this->_markerId[$key] = 'mk' . $this->mapId . static::$markerCount++;

			if ($prefix) {
				$this->_markerId[$key] = $prefix . ucfirst($this->_markerId[$key]);
			}
		}

		return $this->_markerId[$key];
	}

	/**
	 * @return string the JS variable name of map object.
	 */
	private function gJsMapVarName()
	{
		return 'map' . $this->mapId;
	}

	/**
	 * @param array $marker a 1d array of marker position.
	 * @param string $prefix prefix for prepend to JS variable name.
	 * @return string the JS variable name for `$maker` position object.
	 */
	private function gJsPositionVarName($marker, $prefix = null)
	{
		$positionVarName = 'position' . $this->getMarkerId($marker);

		if ($prefix) {
			$positionVarName = $prefix . ucfirst($positionVarName);
		}

		return $positionVarName;
	}

	/**
	 * @param array $marker a 1d array of marker position.
	 * @param string $prefix prefix for prepend to JS variable name.
	 * @return string the JS variable name for `$marker` marker object.
	 */
	private function gJsMarkerVarName($marker, $prefix = null)
	{
		$markerVarName = 'marker' . $this->getMarkerId($marker);

		if ($prefix) {
			$markerVarName = $prefix . ucfirst($markerVarName);
		}

		return $markerVarName;
	}

	/**
	 * @return string the HTML ID of map box
	 */
	private function htmlIdOfMap()
	{
		return 'gDiv' . ucfirst($this->gJsMapVarName());
	}

	/**
	 * @param array $marker a 1d array of marker position.
	 * @param string $prefix prefix for prepend to JS variable name.
	 * @return string return JS definition of position object.
	 */
	private function createGJsPositionObject($marker, $prefix = null)
	{
		$marker = $this->pointToIndArray($marker);
		$jsPositionVarName = $this->gJsPositionVarName($marker, $prefix);

		$strPosition = "\nvar "
					 . $jsPositionVarName
					 . " = new google.maps.LatLng("
					 . "$marker[0], $marker[1]"
					 . ");\n";

		return $strPosition;
	}

	/**
	 * @param array $marker a 1d array of marker position.
	 * @param string $prefix prefix for prepend to marker id
	 * @return string return JS definition of marker object.
	 */
	private function createGJsMarkerObject($marker, $prefix = null)
	{
		$markerJsVarName = $this->gJsPositionVarName($marker, $prefix);
		$markerPosJsVarName = $this->gJsMarkerVarName($marker, $prefix);
		$mapJsVarName = $this->gJsMapVarName();

		$strMarker = "\nvar "
				   . $markerJsVarName
				   . " = new google.maps.Marker({\n"
				   . "		position: $markerJsVarName,\n"
				   . "		map: $mapJsVarName\n"
				   . "});\n";
		return $strMarker;
	}

	/**
	 * @param array a 1d array of marker position.
	 * @return string return JS definition of map object.
	 */
	private function createGJsMapObejct()
	{
		$mapDivId = $this->htmlIdOfMap();
		$centerJsVarName = $this->gJsPositionVarName($this->center, 'c');

		$strMap = "\nvar "
			    . $this->gJsMapVarName()
			    . " = new google.maps.Map(document.getElementById('$mapDivId'), {\n"
			    . "		center: $centerJsVarName,\n"
			    . "		zoom: {$this->zoom},\n"
			    . "		disableDefaultUI: {$this->disableDefaultUI}\n"
			    . "});\n";


		return $strMap;
	}

	private function generateMarkersJsCode($marks)
	{
		$strMarkersPosition = '';

		if (ArrayHelper::isIndexed($marks) && (is_numeric($marks[0]) || isset($marks['latitude']))) {
			$strMarkersPosition .= $this->createGJsPositionObject($marks);
		} else {
			foreach ($this->marks as $mark) {
				$strMarkersPosition .= $this->generateMarkersJsCode($mark);
			}
		}

		return $strMarkersPosition;
	}

	/**
	 * Convert associative position array to an indexed one.
	 * 
	 * @param array a 1d array of marker position.
	 * @return array Indexed version of input.
	 */
	private function pointToIndArray($marker)
	{
		if (ArrayHelper::isIndexed($marker, true)) {
			return $marker;
		}

		return [$marker['latitude'], $marker['longitude']];
	}

	/**
	 * Deterime an array is on correct format or not that mentiond on `$marks`
	 * definition section.
	 * 
	 * @param array $marks Array of points for check to be on correct format.
	 * @return boolean Whether the array is on correct format
	 */
	private function isCorrectFormat($marks)
	{
		if (!is_array($marks) || empty($marks)) {
			return false;
		} elseif (ArrayHelper::isIndexed($marks, true)) {
			if (count($marks) == 2 && isset($marks[0]) && isset($marks[1]) && is_numeric($marks[0]) && is_numeric($marks[1])) {
				return true;
			} else {
				foreach ($marks as $mark) {
					if (!$this->isCorrectFormat($mark)) {
						return false;
					}
				}

				return true;
			}
		} elseif (count($marks) == 2 && isset($marks['latitude']) && isset($marks['longitude']) && is_numeric($marks['latitude']) && is_numeric($marks['longitude'])) {
			return true;
		}

		return false;
	}
}
