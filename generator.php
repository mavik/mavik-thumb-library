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

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('mavik.thumb.info');

/**
 * Generator of thumbnails
 *
 * <code> 
 * Options {
 *   thumbDir: Directory for thumbnails
 *   subDirs: Create subdirectories in thumbnail derectory
 *   copyRemote: Copy remote images
 *   remoteDir: Directory for copying remote images or info about them
 *   quality: Quality of jpg-images
 *   resizeType: Method of resizing
 * }
 * </code> 
 */
class MavikThumbGenerator extends JObject {
    
    /**
     * Options
     * 
     * @var array
     */
    var $options = array(
        'thumbDir' => 'images/thumbnails', // Directory for thumbnails
        'subDirs' => false, // Create subdirectories in thumbnail derectory
        'copyRemote' => false, // Copy remote images
        'remoteDir' => 'images/remote', // Directory for copying remote images or info about them
        'quality' => 90, // Quality of jpg-images
        'resizeType' => 'fit', // Method of resizing
    );

    /**
     * Strategy of resizing
     * 
     * @var MavikThumbResizeType
     */
    var $resizeStrategy;
    
    /**
     * Initialisation of library
     * 
     * @param array $options 
     */
    public function __construct(array $options = array())
    {
        // Check the server requirements only one
        static $checked = false;
        if(!$checked) {
            $this->checkRequirements();
            $checked = true;
        }
        
        // Set options
        $this->options = array_merge($this->options, $options);
        
        // Check and create, if it's need, directories
        $indexFile = '<html><body bgcolor="#FFFFFF"></body></html>';
        $dir = JPATH_SITE.DS.$this->options['thumbDir'];
        if (!JFolder::exists($dir)) {
            if (!JFolder::create($dir, 0777)) {
                $this->setError(JText::_('Can\'t create directory').': '.$dir);
                $this->setError(JText::_('Change the permissions for parent folder to 777'));
            }
            JFile::write($dir.DS.'index.html', $indexFile);
        }
        $dir = JPATH_SITE.DS.$this->options['remoteDir'];
        if (!JFolder::exists($dir)) {
            if (!JFolder::create($dir, 0777)) {
                $this->setError(JText::_('Can\'t create directory').': '.$dir);
                $this->setError(JText::_('Change the permissions for parent folder to 777'));
            }
            JFile::write($dir.DS.'index.html', $indexFile);
        }
        
        // Include resize class
        $this->setResizeType($this->options['resizeType']);
        
    }     

    /**
     * Check the server requirements 
     */
    protected function checkRequirements()
    {
        // Check version of GD
        if (!function_exists('imagecreatetruecolor')) {
                $this->setError(JText::_('Library mAvik Thumb needs library GD2'));
        }
    }
    
    /**
     * Set resize type
     * 
     * @param string $type 
     */
    protected function setResizeType($type)
    {
        jimport("mavik.thumb.resizetype.$type");
        $class = 'MavikThumbResize'.ucfirst($type);
        $this->resizeStrategy = new $class;
    }

    /**
     * Get thumbnail, create if it not exist
     * 
     * @param string $src Path or URI of image
     * @param int $width Width of thumbnail
     * @param int $height Height of thumbnail
     * @return MavikThumbInfo
     */
    public function getThumb($src, $width = 0, $height = 0)
    {
        $info = $this->getImageInfo($src, $width, $height);
    
        // Is there thumbnail in cache?
        if($this->thumbExists($info)) {
            return $info;
        } else {
            // There isn't thumbnail in cache
        }
    }

    /**
     * Get info about original image and thumbnail
     * 
     * @param string $src Path or url to original image
     * @param type $width Desired width for thumbnail
     * @param type $height Desired height for thumbnail
     */
    protected function getImageInfo($src, $width, $height)
    {
        $info = new MavikThumbInfo();
        $this->getOriginalPath($src, $info);
        $this->getOriginalSize($info);
        $this->setThumbSize($info, $width, $height);
        $this->setThumbPath($info);
    }

    /**
     * Get info about URL and path of original image.
     * And copy remote image if it's need.
     * 
     * @param string $src
     * @param MavikThumbInfo
     */
    protected function getOriginalPath($src, MavikThumbInfo $info)
    {
        /*
         *  Is it URL or PATH?
         */
        if(file_exists($src) || file_exists(JPATH_ROOT.DS.$src)) {
            /*
             *  $src IS PATH
             */
            $info->original->local = true;
            $info->original->path = $this->pathToAbsolute($src);
            $info->original->url = $this->pathToUrl($info->original->path);
        } else {
            /*
             *  $src IS URL
             */
            $info->original->local = $this->isUrlLocal($src);
            
            if($info->original->local) {
                /*
                 * Local image
                 */
                $uri = JURI::getInstance($src);
                $info->original->url = $uri->getPath();
                $info->original->path = $this->urlToPath($src);
            } else {
                /*
                 * Remote image
                 */               
                if($this->options['copyRemote'] && $this->options['remoteDir'] ) {
                    // Copy remote image
                    $fileName = $this->getSafeName($src);
                    $localFile = JPATH_ROOT.DS.$this->options['remoteDir'].DS.$fileName;                    
                    //JFile::copy($src, $localFile); // JFile don't work with url
                    copy($src, $localFile);
                    
                    // New url and path
                    $info->original->path = $localFile;
                    $info->original->url = $this->pathToUrl($localFile);
                } else {
                    // For remote image path is url
                    $info->original->path = $src;
                    $info->original->url = $src;
                }                
            }
        }
    }

    /**
     * Get size and type of original image
     * 
     * @param MavikThumbInfo $info
     */
    protected function getOriginalSize(MavikThumbInfo $info)
    {
        // Get size and type of image. Use info-file for remote image
        $useInfoFile = !$info->original->local && !$this->options['copyRemote'] && $this->options['remoteDir'];
        if($useInfoFile) {
            $infoFile = $this->getSafeName($info->original->url, $this->options['remoteDir'], '', 'info');
            if(file_exists($infoFile)) {
                $size = unserialize(file_get_contents($infoFile));
            }
        }
        if (!isset($size)) {
            $size = getimagesize($info->original->path);
            if($useInfoFile) {
                file_put_contents($infoFile, serialize($size));
            }
        }
        
        // Put values to $info
        $info->original->width = $size[0];
        $info->original->height = $size[1];
        $info->original->type = $size['mime'];
        $info->original->size = @filesize($info->original->path);
    }

    /**
     * Set thumbanil size
     * 
     * @param MavikThumbInfo $info
     * @param int $width
     * @param int $height
     */
    protected function setThumbSize(MavikThumbInfo $info, $width, $height)
    {
        // Set widht or height if it is 0
        if ($width == 0) $width = intval($height * $info->original->width / $info->original->height); 
        if ($height == 0) $height = intval($width * $info->original->height / $info->original->width);
        
        $this->resizeStrategy->setSize($info, $width, $height);
    }
    
    /**
     * Set path and url of thumbnail
     * 
     * @param MavikThumbInfo $info
     */
    protected function setThumbPath(MavikThumbInfo $info)
    {
        $suffix = "-{$info->thumbnail->width}x{$info->thumbnail->height}";
        $info->thumbnail->path = $this->getSafeName($info->original->path, $this->options['thumbDir'], $suffix);
        $info->thumbnail->url = $this->pathToUrl($info->thumbnail->path);
    }   

    /**
     * Get absolute path
     * 
     * @param string $path
     * @return string 
     */
    protected function pathToAbsolute($path)
    {
        // $paht is c:\<path> or \<path> or /<path> or <path>
        if (!preg_match('/^\\\|\/|([a-z]\:)/i', $path)) $path = JPATH_ROOT.DS.$path;
        return realpath($path);
    }

    /**
     * Get URL from absolute path
     * 
     * @param string $path
     * @return string
     */
    protected function pathToUrl($path)
    {
        $base = JURI::base(true);
        $path = substr($path, strlen(JPATH_SITE));
        
        return $base.str_replace(DS, '/', $path);
    }
        
    /**
     * Is URL local?
     * 
     * @param string $url
     * @return boolean
     */
    protected function isUrlLocal($url)
    {
        $siteUri = JFactory::getURI();
        $imgUri = JURI::getInstance($url);

        $siteHost = $siteUri->getHost();
        $imgHost = $imgUri->getHost();
        // ignore www in host name
        $siteHost = preg_replace('/^www\./', '', $siteHost);
        $imgHost = preg_replace('/^www\./', '', $imgHost);
        
        return (empty($imgHost) || $imgHost == $siteHost);
    }        

    /**
     * Get safe name
     * 
     * @param string $path Path to file
     * @param string $dir Directory for result file
     * @param string $suffix Suffix for name of file (example size for thumbnail)
     * @param string $ext New extension
     * @return string 
     */
    protected function getSafeName($path, $dir, $suffix = '', $ext = null)
    {
        if(!$this->options['subDirs']) {
            // Without subdirs
            $name = JFile::makeSafe(str_replace(array('/','\\'), '-', $path));
            $name = JFile::stripExt($name).$suffix.'.'.($ext ? $ext : JFile::getExt($name));
            $path = JPATH_ROOT.DS.$dir.DS.$name; 
        } else {
            // With subdirs
            $name = JFile::makeSafe(JFile::getName($path));
            $name = JFile::stripExt($name).$suffix.'.'.($ext ? $ext : JFile::getExt($name));
            $path = JPATH_BASE.DS.$dir.DS.$path;
            $path = str_replace('\\', DS, $path);
            $path = str_replace('/', DS, $path);
            $path = substr($path, 0, strrpos($path, DS));
            if(!JFolder::exists($path)) {
                JFolder::create($path, 0777);
                $indexFile = '<html><body bgcolor="#FFFFFF"></body></html>';
                JFile::write($path.DS.'index.html', $indexFile);
            }
            $path = $path . DS . $name;            
        }
        
        return $path;
    }
    
    /**
    * Convert local url to path
    * 
    * @param string $url
    */
    protected static function urlToPath($url)
    {
        $imgUri = JURI::getInstance($url);
        $path = $imgUri->getPath();
        $base = JURI::base(true);
        if($base && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        return realpath(JPATH_ROOT.DS.str_replace('/', DS, $path));
    }
    
    protected function thumbExists(MavikThumbInfo $info)
    {
        if(file_exists($info->thumbnail->path)) {
            $infoFile = '';
        }
    }
}
?>
