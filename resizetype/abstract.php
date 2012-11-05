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

/**
 * Strategy of resizing
 * Parent class
 */
abstract class MavikThumbResizeType
{
    /**
     * Set thumnail size
     * 
     * @param MavikThumbInfo $info
     * @param int $width
     * @param int $height
     */
    public function setSize(MavikThumbInfo $info, $width, $height)
    {
        $info->thumbnail->width = $width;
        $info->thumbnail->height = $height;
    }
    
    /**
     * Coordinates and size of area in the original image
     * 
     * @return array
     */
    function getArea(MavikThumbInfo $info)
    {
        return array(0, 0, $info->original->width, $info->original->height);
    }
    
}
?>
