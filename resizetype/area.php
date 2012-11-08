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
 * Keep area
 */
class MavikThumbResizeArea extends MavikThumbResizeType {
    
    public function  setSize(MavikThumbInfo $info, $width, $height)
    {   
        $thumbArea = $width * $height;
        $originArea = $info->original->width * $info->original->height;
        $ratio = sqrt($originArea/$thumbArea);
        $info->thumbnail->width = round($info->original->width/$ratio);
        $info->thumbnail->height = round($info->original->height/$ratio);
    }    
}
?>
