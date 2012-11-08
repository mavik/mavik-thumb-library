<?php
/**
 * Library for Joomla for creating thumbnails of images
 * 
 * @package Mavik Thumb
 * @version 1.0
 * @author Vitaliy Marenkov <admin@mavik.com.ua>
 * @copyright 2012 Vitaliy Marenkov
 */

defined( '_JEXEC' ) or die;

jimport('mavik.thumb.resizetype.abstract');

/**
 * Fill area and crop image
 */
class MavikThumbResizeFill extends MavikThumbResizeType {

    public function getArea(MavikThumbInfo $info)
    {
        $thumbWidht = $info->thumbnail->width;
        $thumbHeight = $info->thumbnail->height;
        $origWidth = $info->original->width;
        $origHeight = $info->original->height;
        if ($origWidth/$origHeight < $thumbWidht/$thumbHeight) {
                $x = 0; $widht = $origWidth;
                $height = $origWidth *  $thumbHeight/$thumbWidht;
                $y = ($origHeight - $height)/2;
        } else {
                $y = 0; $height = $origHeight;
                $widht = $origHeight *  $thumbWidht/$thumbHeight;
                $x = ($origWidth - $widht)/2;
        }
        return array($x, $y, $widht, $height);
    }    
    
}

?>
