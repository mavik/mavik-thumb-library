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
class MavikThumbResizeType
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
}
?>
