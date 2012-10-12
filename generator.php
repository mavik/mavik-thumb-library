<?php
/**
 * Library for Joomla for creating thumbnails of images
 * 
 * @package Mavik Thumb
 * @version 1.0
 * @author Vitaliy Marenkov <admin@mavik.com.ua>
 * @copyright 2012 Vitaliy Marenkov
 */

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
    public function __construct(array $options)
    {
        // Check the server requirements only one
        static $checked = false;
        if(!$checked) {
            $this->checkRequirements();
            $checked = true;
        }
        
        // Set options
        $this->options = array_merge($this->options, $options);
        
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
    public function getThumb(string $src, int $width = 0, int $height = 0)
    {
        $info = new MavikThumbInfo();
        
        $this->getOriginalInfoPath($src, $info);
    
        
       
        
        // Доопределить размеры, если необходимо
        if ($width == 0) {
            $width = intval($height * $info->size[0] / $info->size[1]); 
        }
        if ($height == 0) {
            $height(intval($width * $info->sizcreateThumbe[1] / $info->size[0]));
        }
        
        // Сформировать путь к иконке
        $thumbName = $this->getSafeName($this->origImgName);
        $thumbName = JFile::stripExt($thumbName) . '-'.$this->img->getWidth() . 'x' . $this->img->getHeight().'.'.JFile::getExt($thumbName);
        $thumbPath = JPATH_BASE . DS . $this->thumbPath . DS . $thumbName; 
        // Если иконки не существует - создать
        if (!file_exists($thumbPath))
        {
                // Проверить хватит ли памяти
                $allocatedMemory = ini_get('memory_limit')*1048576 - memory_get_usage(true);
                $neededMemory = $this->origImgSize[0] * $this->origImgSize[1] * 4;
                $neededMemory *= 1.25; // Прибавляем 25% на накладные расходы
                if ($neededMemory >= $allocatedMemory) {
                        $this->originalSrc = $this->img->getAttribute('src');
                        $this->img->setAttribute('src', '');
                        $app = &JFactory::getApplication();
                        $app->enqueueMessage(JText::_('You use too big image'), 'error');
                        return;
                }

                // Определить тип оригинального изображения
                $mime = $this->origImgSize['mime'];
                // В зависимости от этого создать объект изобразения
                switch ($mime)
                {
                        case 'image/jpeg':
                                $orig = imagecreatefromjpeg($this->origImgName);
                                break;
                        case 'image/png':
                                $orig = imagecreatefrompng($this->origImgName);
                                break;
                        case 'image/gif':
                                $orig = imagecreatefromgif($this->origImgName);
                                break;
                        default:
                                // Если тип не поддерживается - вернуть тег без изменений
                                $this->originalSrc = $this->img->getAttribute('src');
                                return;
                }
                // Создать объект иконки
                $thumb = imagecreatetruecolor($this->img->getWidth(), $this->img->getHeight());
                // Обработать прозрачность
                if ($mime == 'image/png' || $mime == 'image/gif') {
                        $transparent_index = imagecolortransparent($orig);
                        if ($transparent_index >= 0 && $transparent_index < imagecolorstotal($orig))
                        {
                                // без альфа-канала
                                $t_c = imagecolorsforindex($orig, $transparent_index);
                                $transparent_index = imagecolorallocate($orig, $t_c['red'], $t_c['green'], $t_c['blue']);
                                imagefilledrectangle( $thumb, 0, 0, $this->img->getWidth(), $this->img->getHeight(), $transparent_index );
                                imagecolortransparent($thumb, $transparent_index);
                        }
                        if ($mime == 'image/png') {
                                // с альфа-каналом
                                imagealphablending ( $thumb, false );
                                imagesavealpha ( $thumb, true );
                                $transparent = imagecolorallocatealpha ( $thumb, 255, 255, 255, 127 );
                                imagefilledrectangle( $thumb, 0, 0, $this->img->getWidth(), $this->img->getHeight(), $transparent );
                        }
                }

                // Создать превью
                list($x, $y, $widht, $height) = $this->proportionsStrategy->getArea();
                imagecopyresampled($thumb, $orig, 0, 0, $x, $y, $this->img->getWidth(), $this->img->getHeight(), $widht, $height);
                // Записать иконку в файл
                switch ($mime)
                {
                        case 'image/jpeg':
                                if (!imagejpeg($thumb, $thumbPath, $this->quality)) {
                                        $this->errorCreateFile($thumbPath);
                                }
                                break;
                        case 'image/png':
                                if (!imagepng($thumb, $thumbPath)) {
                                        $this->errorCreateFile($thumbPath);
                                }
                                break;
                        case 'image/gif':
                                if (!imagegif($thumb, $thumbPath)) {
                                        $this->errorCreateFile($thumbPath);
                                }
                }
                imagedestroy($orig);
                imagedestroy($thumb);
        }
        $this->originalSrc = $this->img->getAttribute('src');
        $this->img->setAttribute('src', $this->thumbPath . '/' . $thumbName);
    }

    
    
    
    /**
     * Get info about URL and path of original image.
     * And copy remote image if it's need.
     * 
     * @param string $src
     * @param MavikThumbInfo
     */
    protected function getOriginalInfoPath(string $src, MavikThumbInfo $info)
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
            $info->original->url = $this->pathToUrl($src);
        } else {
            /*
             *  $src IS URL
             */
            $info->original->local = $this->isUrlLocal($src);
            
            if($info->original->local) {
                /*
                 * Local image
                 */
                $info->original->url = $this->urlToAbsolute($src);
                $info->original->path = $this->urlToPath($info->original->url);
            } else {
                /*
                 * Remote image
                 */               
                if($this->options['copyRemote'] && $this->options['remoteDir'] ) {
                    // Copy remote image
                    $fileName = $this->getSafeName($src);
                    $localFile = JPATH_ROOT.DS.$this->options['remoteDir'] . DS . $fileName;                    
                    JFile::copy($src, $localFile); // Родная функция не работает с url
                    //copy($src, $localFile);
                    
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
     * Get safe name
     * 
     * @param string $name
     * @return string 
     */
    private function getSafeName($name)
    {
        return JFile::makeSafe(str_replace(array('/','\\'), '-', $name));
    }
    
    /**
        * Преобразует url-путь в путь к файлу
        * если хост в url совпадает с url сайта,
        * иначе оставляет без изменений
        *
        * @param string $url
        */
    public static function urlToPath($url)
    {
            $siteUri = JFactory::getURI();
            $imgUri = JURI::getInstance($url);

            $siteHost = $siteUri->getHost();
            $imgHost = $imgUri->getHost();
            // игнорировать www при сверке хостов 
            $siteHost = preg_replace('/^www\./', '', $siteHost);
            $imgHost = preg_replace('/^www\./', '', $imgHost);
            if (empty($imgHost) || $imgHost == $siteHost) {
                    $imgPath = $imgUri->getPath(); 
                    // если путь к изображению абсолютный от корня домена (начинается со слеша),
                    // преобразовать его в относительный от базового адреса сайта
                    if ($imgPath[0] == '/')	{
                            $siteBase = $siteUri->base();
                            $dirSite = substr($siteBase, strpos($siteBase, $siteHost) + strlen($siteHost));
                            $url = substr($imgPath, strlen($dirSite));
                    }
                    $url = urldecode(str_replace('/', DS, $url));
            }
            return $url;
    }
    
    
}
?>
