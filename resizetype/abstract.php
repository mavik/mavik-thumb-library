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
        switch ($this->getDefaultDimension($info, $width, $height)) {
            case 'w':
                $info->thumbnail->width = $width;
                $info->thumbnail->height = round($info->original->height * $width/$info->original->width);
                break;
            case 'h':
                $info->thumbnail->height = $height;
                $info->thumbnail->width = round($info->original->width * $height/$info->original->height);
                break;
            default:
                $info->thumbnail->width = $width;
                $info->thumbnail->height = $height;
        }
    }
    
    /**
     * Coordinates and size of area in the original image
     * 
     * @return array
     */
    public function getArea(MavikThumbInfo $info)
    {
        return array(0, 0, $info->original->width, $info->original->height);
    }
    
    /**
     * Which dimension to use: width or heigth or width and heigth?
     * 
     * @return string
     */
    protected function getDefaultDimension(MavikThumbInfo $info, $width, $height)
    {
            return 'wh';
    }
}
?>
