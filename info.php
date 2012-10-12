<?php
/**
 * Library for Joomla for creating thumbnails of images
 * 
 * @package Mavik Thumb
 * @version 1.0
 * @author Vitaliy Marenkov <admin@mavik.com.ua>
 * @copyright 2012 Vitaliy Marenkov
 */

/**
 * Information about thumbnail and original image
 */
class MavikThumbInfo {
   
    /**
     * Info about original image
     * 
     * @var MavikThumbImageInfo
     */
    var $original;
    
    /**
     * Info about thumbnail
     * 
     * @var MavikThumbImageInfo
     */    
    var $thumbnail;

    public function __construct() {
        $this->original = new MavikThumbImageInfo();
        $this->thumbnail = new MavikThumbImageInfo();
    }
}

/**
 *  Information about image
 */
class MavikThumbImageInfo {
    var $url = null;
    var $path = null;
    var $width = null;
    var $height = null;
    var $size = null;
    var $type = null;
    var $local = null;
}
?>
