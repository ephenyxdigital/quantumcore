<?php

use WebPConvert\WebPConvert;

/**
 * Class ImageManager
 *
 * @since 1.9.1.0
 */
class ImageManager {

    public static $instance;

    public $context;

    const ERROR_FILE_NOT_EXIST = 1;
    const ERROR_FILE_WIDTH = 2;
    const ERROR_MEMORY_LIMIT = 3;

    public function __construct() {

        $this->context = Context::getContext();

        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();
        }

        if (!isset($this->context->company)) {
            $this->context->company = Company::initialize();
        }

        if (!isset($this->context->_tools)) {
            $this->context->_tools = PhenyxTool::getInstance();
        }

        if (!isset($this->context->theme)) {
            $defaul_theme = Theme::buildObject($this->context->company->id_theme);
            $this->context->theme = $defaul_theme;
        }

        if (!isset($this->context->_hook)) {
            $this->context->_hook = Hook::getInstance();
        }

        $this->context->img_manager = $this;

    }

    public static function getInstance() {

        if (!static::$instance) {
            static::$instance = new ImageManager();
        }

        return static::$instance;
    }

    public function thumbnail($image, $cacheImage, $size, $imageType = 'jpg', $disableCache = true, $regenerate = false) {

        if (!file_exists($image)) {
            return '';
        }

        if (file_exists(_EPH_TMP_IMG_DIR_ . $cacheImage) && $regenerate) {
            @unlink(_EPH_TMP_IMG_DIR_ . $cacheImage);
        }

        if ($regenerate || !file_exists(_EPH_TMP_IMG_DIR_ . $cacheImage)) {
            $infos = getimagesize($image);

            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.

            if (!$this->checkImageMemoryLimit($image)) {
                return false;
            }

            $x = $infos[0];
            $y = $infos[1];
            $maxX = $size * 3;

            // Size is already ok

            if ($y < $size && $x <= $maxX) {
                copy($image, _EPH_TMP_IMG_DIR_ . $cacheImage);
            }

            // We need to resize */
            else {
                $ratio_x = $x / ($y / $size);

                if ($ratio_x > $maxX) {
                    $ratio_x = $maxX;
                    $size = $y / ($x / $maxX);
                }

                $this->resize($image, _EPH_TMP_IMG_DIR_ . $cacheImage, $ratio_x, $size, $imageType);
            }

        }

        // Relative link will always work, whatever the base uri set in the admin

        if (Context::getContext()->controller->controller_type == 'admin') {
            return '<img src="../img/tmp/' . $cacheImage . ($disableCache ? '?time=' . time() : '') . '" alt="" class="imgm img-thumbnail" />';
        } else {
            return '<img src="' . _EPH_TMP_IMG_ . $cacheImage . ($disableCache ? '?time=' . time() : '') . '" alt="" class="imgm img-thumbnail" />';
        }

    }

    public function checkImageMemoryLimit($image) {

        $infos = @getimagesize($image);

        if (!is_array($infos) || !isset($infos['bits'])) {
            return true;
        }

        $memoryLimit = $this->context->_tools->getMemoryLimit();
        // memory_limit == -1 => unlimited memory

        if (isset($infos['bits']) && function_exists('memory_get_usage') && (int) $memoryLimit != -1) {
            $currentMemory = memory_get_usage();
            $bits = $infos['bits'] / 8;
            $channel = isset($infos['channels']) ? $infos['channels'] : 1;

            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            // For perfs, avoid computing static maths formulas in the code. pow(2, 16) = 65536 ; 1024 * 1024 = 1048576

            if (($infos[0] * $infos[1] * $bits * $channel + 65536) * 1.8 + $currentMemory > $memoryLimit - 1048576) {
                return false;
            }

        }

        return true;
    }

    public function resize(
        $srcFile,
        $dstFile,
        $dstWidth = null,
        $dstHeight = null,
        $fileType = 'jpg',
        $forceType = false,
        &$error = 0,
        &$tgtWidth = null,
        &$tgtHeight = null,
        $quality = 5,
        &$srcWidth = null,
        &$srcHeight = null
    ) {

        clearstatcache(true, $srcFile);

        if (!file_exists($srcFile) || !filesize($srcFile)) {
            return !($error = static::ERROR_FILE_NOT_EXIST);
        }

        list($tmpWidth, $tmpHeight, $type) = getimagesize($srcFile);
        $rotate = 0;

        if (function_exists('exif_read_data') && function_exists('mb_strtolower')) {
            $exif = @exif_read_data($srcFile);

            if ($exif && isset($exif['Orientation'])) {

                switch ($exif['Orientation']) {
                case 3:
                    $srcWidth = $tmpWidth;
                    $srcHeight = $tmpHeight;
                    $rotate = 180;
                    break;

                case 6:
                    $srcWidth = $tmpHeight;
                    $srcHeight = $tmpWidth;
                    $rotate = -90;
                    break;

                case 8:
                    $srcWidth = $tmpHeight;
                    $srcHeight = $tmpWidth;
                    $rotate = 90;
                    break;

                default:
                    $srcWidth = $tmpWidth;
                    $srcHeight = $tmpHeight;
                }

            } else {
                $srcWidth = $tmpWidth;
                $srcHeight = $tmpHeight;
            }

        } else {
            $srcWidth = $tmpWidth;
            $srcHeight = $tmpHeight;
        }

        // If EPH_IMAGE_QUALITY is activated, the generated image will be a PNG with .jpg as a file extension.
        // This allow for higher quality and for transparency. JPG source files will also benefit from a higher quality
        // because JPG reencoding by GD, even with max quality setting, degrades the image.

        if ($fileType !== 'webp' && (Context::getContext()->phenyxConfig->get('EPH_IMAGE_QUALITY') == 'png_all'
            || (Context::getContext()->phenyxConfig->get('EPH_IMAGE_QUALITY') == 'png' && $type == IMAGETYPE_PNG) && !$forceType)
        ) {
            $fileType = 'png';
        }

        if (!$srcWidth) {
            return !($error = static::ERROR_FILE_WIDTH);
        }

        if (!$dstWidth) {
            $dstWidth = $srcWidth;
        }

        if (!$dstHeight) {
            $dstHeight = $srcHeight;
        }

        $widthDiff = $dstWidth / $srcWidth;
        $heightDiff = $dstHeight / $srcHeight;

        $psImageGenerationMethod = Context::getContext()->phenyxConfig->get('EPH_IMAGE_GENERATION_METHOD');

        if ($widthDiff > 1 && $heightDiff > 1) {
            $nextWidth = $srcWidth;
            $nextHeight = $srcHeight;
        } else {

            if ($psImageGenerationMethod == 2 || (!$psImageGenerationMethod && $widthDiff > $heightDiff)) {
                $nextHeight = $dstHeight;
                $nextWidth = round(($srcWidth * $nextHeight) / $srcHeight);
                $dstWidth = (int) (!$psImageGenerationMethod ? $dstWidth : $nextWidth);
            } else {
                $nextWidth = $dstWidth;
                $nextHeight = round($srcHeight * $dstWidth / $srcWidth);
                $dstHeight = (int) (!$psImageGenerationMethod ? $dstHeight : $nextHeight);
            }

        }

        if (!$this->checkImageMemoryLimit($srcFile)) {
            return !($error = static::ERROR_MEMORY_LIMIT);
        }

        $tgtWidth = $dstWidth;
        $tgtHeight = $dstHeight;

        $destImage = imagecreatetruecolor($dstWidth, $dstHeight);

        // If image is a PNG or WEBP and the output is PNG/WEBP, fill with transparency. Else fill with white background.

        if ($fileType == 'png' && $type == IMAGETYPE_PNG || $fileType === 'webp') {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
            imagefilledrectangle($destImage, 0, 0, $dstWidth, $dstHeight, $transparent);
        } else {
            $white = imagecolorallocate($destImage, 255, 255, 255);
            imagefilledrectangle($destImage, 0, 0, $dstWidth, $dstHeight, $white);
        }

        $srcImage = $this->create($type, $srcFile);

        if ($rotate) {
            $srcImage = imagerotate($srcImage, $rotate, 0);
        }

        if ($dstWidth >= $srcWidth && $dstHeight >= $srcHeight) {
            imagecopyresized($destImage, $srcImage, (int) (($dstWidth - $nextWidth) / 2), (int) (($dstHeight - $nextHeight) / 2), 0, 0, $nextWidth, $nextHeight, $srcWidth, $srcHeight);
        } else {
            $this->imagecopyresampled($destImage, $srcImage, (int) (($dstWidth - $nextWidth) / 2), (int) (($dstHeight - $nextHeight) / 2), 0, 0, $nextWidth, $nextHeight, $srcWidth, $srcHeight, $quality);
        }

        $writeFile = $this->write($fileType, $destImage, $dstFile);
        Tool::resizeImg($dstFile);
        //$this->context->_hook->exec('actionOnImageResizeAfter', array('dst_file' => $dstFile, 'file_type' => $fileType));
        @imagedestroy($srcImage);

        return $writeFile;
    }

    public function actionOnImageResizeAfter($dstFile, $newFile) {

        $webp = WebPGeneratorConfig::getInstance();
        $config = $webp->getConverterSettings();

        try {

            return WebPConvert::convert(
                $dstFile,
                $newFile,
                $config
            );
        } catch (Exception $exception) {

            $file = fopen("testImageUploadAfter.txt", "w");
            fwrite($file, $exception->getMessage());
            $return = [
                'success' => false,
                'error'   => empty($exception->getMessage()) ? 'Unknown error' : $exception->getMessage(),
            ];
            die($this->context->_tools->jsonEncode($return));
        }

    }

    public function actionOnImageUploadAfter($dstFile, $newFile) {

        $webp = new WebPGeneratorConfig();
        $config = $webp->getConverterSettings();
        try {

            return WebPConvert::convert(
                $dstFile,
                $newFile,
                $config
            );
        } catch (Exception $exception) {

            fwrite($file, $exception->getMessage());
            return [
                'success' => false,
                'error'   => empty($exception->getMessage()) ? 'Unknown error' : $exception->getMessage(),
            ];

        }

    }

    protected function resizeWebP($destination) {

        if (file_exists($destination) && !unlink($destination)) {
            throw new FileErrorException();
        }

        if (file_exists($destination . '.webp')) {
            return true;
        }

        $dstFile = $destination . '.jpg';
        self::actionOnImageResizeAfter($dstFile);

        return true;
    }

    public function create($type, $filename) {

        switch ($type) {
        case IMAGETYPE_GIF:
            return imagecreatefromgif($filename);

        case IMAGETYPE_PNG:
            return imagecreatefrompng($filename);

        case 18:
            return imagecreatefromwebp($filename);

        case IMAGETYPE_JPEG:
        default:
            return imagecreatefromjpeg($filename);
            break;
        }

    }

    public function imagecopyresampled(&$dstImage, $srcImage, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH, $quality = 3) {

        if (empty($srcImage) || empty($dstImage) || $quality <= 0) {
            return false;
        }

        if ($quality < 5 && (($dstW * $quality) < $srcW || ($dstH * $quality) < $srcH)) {
            $temp = imagecreatetruecolor($dstW * $quality + 1, $dstH * $quality + 1);
            imagecopyresized($temp, $srcImage, 0, 0, $srcX, $srcY, $dstW * $quality + 1, $dstH * $quality + 1, $srcW, $srcH);
            imagecopyresampled($dstImage, $temp, $dstX, $dstY, 0, 0, $dstW, $dstH, $dstW * $quality, $dstH * $quality);
            imagedestroy($temp);
        } else {
            imagecopyresampled($dstImage, $srcImage, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
        }

        return true;
    }

    public function write($type, $resource, $filename) {

        static $psPngQuality = null;
        static $psJpegQuality = null;
        static $psWebpQuality = null;

        if ($psPngQuality === null) {
            $psPngQuality = Context::getContext()->phenyxConfig->get('EPH_PNG_QUALITY');
        }

        if ($psJpegQuality === null) {
            $psJpegQuality = Context::getContext()->phenyxConfig->get('EPH_JPEG_QUALITY');
        }

        if ($psWebpQuality === null) {
            $psWebpQuality = Context::getContext()->phenyxConfig->get('EPH_WEBP_QUALITY');
        }

        switch ($type) {
        case 'gif':
            $success = imagegif($resource, $filename);
            break;

        case 'png':
            $quality = ($psPngQuality === false ? 7 : $psPngQuality);
            $success = imagepng($resource, $filename, (int) $quality);
            break;

        case 'webp':
            $quality = ($psWebpQuality === false ? 90 : $psWebpQuality);
            $success = imagewebp($resource, $filename, (int) $quality);
            break;

        case 'jpg':
        case 'jpeg':
        default:
            $quality = ($psJpegQuality === false ? 90 : $psJpegQuality);
            imageinterlace($resource, 1); /// make it PROGRESSIVE
            $success = imagejpeg($resource, $filename, (int) $quality);
            break;
        }

        imagedestroy($resource);
        @chmod($filename, 0664);

        return $success;
    }

    public function validateUpload($file, $maxFileSize = 0, $types = null) {

        if ((int) $maxFileSize > 0 && $file['size'] > (int) $maxFileSize) {
            return sprintf(Tools::displayError('Image is too large (%1$d kB). Maximum allowed: %2$d kB'), $file['size'] / 1024, $maxFileSize / 1024);
        }

        if (!$this->isRealImage($file['tmp_name'], $file['type']) || !$this->isCorrectImageFileExt($file['name'], $types) || preg_match('/\%00/', $file['name'])) {
            return Tools::displayError('Image format not recognized, allowed formats are: .gif, .jpg, .png');
        }

        if ($file['error']) {
            return sprintf(Tools::displayError('Error while uploading image; please change your server\'s settings. (Error code: %s)'), $file['error']);
        }

        return false;
    }

    public function cleanProductFolder() {

        $productTypes = ImageType::getImagesTypes('products');
        $names = [];

        foreach ($productTypes as $type) {

            $names[] = $type['name'];
        }

        $images = 0;
        $fileToCheck = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_PROD_IMG_DIR_));

        foreach ($iterator as $filename) {

            if (preg_match('/\.(jpg|png|jpeg|webp)$/', $filename->getBasename())) {

                $file = $filename->getPathname();
                $path_parts = pathinfo($file);
                $file = explode('-', $path_parts['filename'])[1];

                if (empty($file)) {
                    $fileToCheck[] = $filename->getPathname();
                    continue;
                } else

                if (!in_array($file, $names)) {
                    unlink($filename->getPathname());
                    $images++;
                }

            }

        }

        foreach ($fileToCheck as $image) {
            $path_parts = pathinfo($image);
            $file = $path_parts['filename'];
            $exist = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow('SELECT id_image, id_product FROM `' . _DB_PREFIX_ . 'image` WHERE id_image = ' . (int) $file);

            if (empty($exist)) {
                unlink($path_parts['dirname'] . DIRECTORY_SEPARATOR . $file . '.jpg');
                $images++;

                foreach ($names as $name) {

                    if (file_exists($path_parts['dirname'] . DIRECTORY_SEPARATOR . $file . '-' . $name . '.jpg')) {
                        unlink($path_parts['dirname'] . DIRECTORY_SEPARATOR . $file . '-' . $name . '.jpg');
                        $images++;
                    }

                }

            }

        }

        return $images;

    }

    public function isRealImage($filename, $fileMimeType = null, $mimeTypeList = null) {

        // Detect mime content type
        $mimeType = false;

        if (!$mimeTypeList) {
            $mimeTypeList = ['image/gif', 'image/jpg', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'];
        }

        // Try 4 different methods to determine the mime type

        if (function_exists('getimagesize')) {
            $imageInfo = @getimagesize($filename);

            if ($imageInfo) {
                $mimeType = $imageInfo['mime'];
            } else {
                $fileMimeType = false;
            }

        } else

        if (function_exists('finfo_open')) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            $finfo = finfo_open($const);
            $mimeType = finfo_file($finfo, $filename);
            finfo_close($finfo);
        } else

        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filename);
        } else

        if (function_exists('exec')) {
            $mimeType = trim(exec('file -b --mime-type ' . escapeshellarg($filename)));

            if (!$mimeType) {
                $mimeType = trim(exec('file --mime ' . escapeshellarg($filename)));
            }

            if (!$mimeType) {
                $mimeType = trim(exec('file -bi ' . escapeshellarg($filename)));
            }

        }

        if ($fileMimeType && (empty($mimeType) || $mimeType == 'regular file' || $mimeType == 'text/plain')) {
            $mimeType = $fileMimeType;
        }

        // For each allowed MIME type, we are looking for it inside the current MIME type

        foreach ($mimeTypeList as $type) {

            if (strstr($mimeType, $type)) {
                return true;
            }

        }

        return false;
    }

    public function isCorrectImageFileExt($filename, $authorizedExtensions = null) {

        // Filter on file extension

        if ($authorizedExtensions === null) {
            $authorizedExtensions = ['gif', 'jpg', 'jpeg', 'jpe', 'png'];
        }

        $nameExplode = explode('.', $filename);

        if (count($nameExplode) >= 2) {
            $current_extension = strtolower($nameExplode[count($nameExplode) - 1]);

            if (!in_array($current_extension, $authorizedExtensions)) {
                return false;
            }

        } else {
            return false;
        }

        return true;
    }

    public function validateIconUpload($file, $maxFileSize = 0) {

        if ((int) $maxFileSize > 0 && $file['size'] > $maxFileSize) {
            return sprintf(
                Tools::displayError('Image is too large (%1$d kB). Maximum allowed: %2$d kB'),
                $file['size'] / 1000,
                $maxFileSize / 1000
            );
        }

        if (substr($file['name'], -4) != '.ico' && substr($file['name'], -4) != '.png') {
            return Tools::displayError('Image format not recognized, allowed formats are: .ico, .png');
        }

        if ($file['error']) {
            return Tools::displayError('Error while uploading image; please change your server\'s settings.');
        }

        return false;
    }

    public function cut($srcFile, $dstFile, $dstWidth = null, $dstHeight = null, $fileType = 'jpg', $dstX = 0, $dstY = 0) {

        if (!file_exists($srcFile)) {
            return false;
        }

        // Source information
        $srcInfo = getimagesize($srcFile);
        $src = [
            'width'     => $srcInfo[0],
            'height'    => $srcInfo[1],
            'ressource' => $this->create($srcInfo[2], $srcFile),
        ];

        // Destination information
        $dest = [];
        $dest['x'] = $dstX;
        $dest['y'] = $dstY;
        $dest['width'] = !is_null($dstWidth) ? $dstWidth : $src['width'];
        $dest['height'] = !is_null($dstHeight) ? $dstHeight : $src['height'];
        $dest['ressource'] = $this->createWhiteImage($dest['width'], $dest['height']);

        $white = imagecolorallocate($dest['ressource'], 255, 255, 255);
        imagecopyresampled($dest['ressource'], $src['ressource'], 0, 0, $dest['x'], $dest['y'], $dest['width'], $dest['height'], $dest['width'], $dest['height']);
        imagecolortransparent($dest['ressource'], $white);
        $return = $this->write($fileType, $dest['ressource'], $dstFile);
        @imagedestroy($src['ressource']);

        return $return;
    }

    public function createWhiteImage($width, $height) {

        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        return $image;
    }

    public function getMimeTypeByExtension($fileName) {

        $types = [
            'image/gif'  => ['gif'],
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png'  => ['png'],
        ];
        $extension = substr($fileName, strrpos($fileName, '.') + 1);

        $mimeType = null;

        foreach ($types as $mime => $exts) {

            if (in_array($extension, $exts)) {
                $mimeType = $mime;
                break;
            }

        }

        if ($mimeType === null) {
            $mimeType = 'image/jpeg';
        }

        return $mimeType;
    }

    public function generateFavicon($source, $sizes = [['16', '16'], ['24', '24'], ['32', '32'], ['48', '48'], ['64', '64']]) {

        $images = [];

        if (!$size = getimagesize($source['save_path'])) {
            return false;
        }

        if (!$file_data = file_get_contents($source['save_path'])) {
            return false;
        }

        if (!$im = imagecreatefromstring($file_data)) {
            return false;
        }

        unset($file_data);

        if (empty($sizes)) {
            $sizes = [imagesx($im), imagesy($im)];
        }

        if (!is_array($sizes[0])) {
            $sizes = [$sizes];
        }

        foreach ((array) $sizes as $size) {
            list($width, $height) = $size;
            $new_im = imagecreatetruecolor($width, $height);
            imagecolortransparent($new_im, imagecolorallocatealpha($new_im, 0, 0, 0, 127));
            imagealphablending($new_im, false);
            imagesavealpha($new_im, true);
            $source_width = imagesx($im);
            $source_height = imagesy($im);

            if (false === imagecopyresampled($new_im, $im, 0, 0, 0, 0, $width, $height, $source_width, $source_height)) {
                continue;
            }

            static::addFaviconImageData($new_im, $images);
        }

        return static::getIcoData($images);
    }

    protected function getIcoData($images) {

        if (!is_array($images) || empty($images)) {
            return false;
        }

        $data = pack('vvv', 0, 1, count($images));
        $pixel_data = '';
        $icon_dir_entry_size = 16;
        $offset = 6 + ($icon_dir_entry_size * count($images));

        foreach ($images as $image) {
            $data .= pack('CCCCvvVV', $image['width'], $image['height'], $image['color_palette_colors'], 0, 1, $image['bits_per_pixel'], $image['size'], $offset);
            $pixel_data .= $image['data'];
            $offset += $image['size'];
        }

        $data .= $pixel_data;
        unset($pixel_data);
        return $data;
    }

    protected function addFaviconImageData($im, &$images) {

        $width = imagesx($im);
        $height = imagesy($im);
        $pixel_data = [];
        $opacity_data = [];
        $current_opacity_val = 0;

        for ($y = $height - 1; $y >= 0; $y--) {

            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($im, $x, $y);
                $alpha = ($color & 0x7F000000) >> 24;
                $alpha = (1 - ($alpha / 127)) * 255;
                $color &= 0xFFFFFF;
                $color |= 0xFF000000 & ($alpha << 24);
                $pixel_data[] = $color;
                $opacity = ($alpha <= 127) ? 1 : 0;
                $current_opacity_val = ($current_opacity_val << 1) | $opacity;

                if ((($x + 1) % 32) == 0) {
                    $opacity_data[] = $current_opacity_val;
                    $current_opacity_val = 0;
                }

            }

            if (($x % 32) > 0) {

                while (($x++ % 32) > 0) {
                    $current_opacity_val = $current_opacity_val << 1;
                }

                $opacity_data[] = $current_opacity_val;
                $current_opacity_val = 0;
            }

        }

        $image_header_size = 40;
        $color_mask_size = $width * $height * 4;
        $opacity_mask_size = (ceil($width / 32) * 4) * $height;
        $data = pack('VVVvvVVVVVV', 40, $width, ($height * 2), 1, 32, 0, 0, 0, 0, 0, 0);

        foreach ($pixel_data as $color) {
            $data .= pack('V', $color);
        }

        foreach ($opacity_data as $opacity) {
            $data .= pack('N', $opacity);
        }

        $image = [
            'width'                => $width,
            'height'               => $height,
            'color_palette_colors' => 0,
            'bits_per_pixel'       => 32,
            'size'                 => $image_header_size + $color_mask_size + $opacity_mask_size,
            'data'                 => $data,
        ];
        $images[] = $image;
    }

    public function webpSupport($checkAccept = false) {

        static $supported = null;

        if ($supported === null) {
            $theme = new Theme($this->context->theme->id);
            $config = $theme->getConfiguration();

            try {
                $supported = Context::getContext()->phenyxConfig->get('EPH_USE_WEBP')
                && !empty($config['webp'])
                && function_exists('imagewebp');
            } catch (PhenyxException $e) {
                $supported = false;
            }

        }

        if ($checkAccept) {
            $supported &= !empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
        }

        return $supported;
    }

    public function retinaSupport() {

        static $supported = null;

        if ($supported === null) {
            try {
                $supported = (bool) Context::getContext()->phenyxConfig->get('EPH_HIGHT_DPI');
            } catch (PhenyxException $e) {
                $supported = false;
            }

        }

        return $supported;
    }

    public function deleteImageType($type) {

        $dir = _EPH_PROD_IMG_DIR_;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_PROD_IMG_DIR_));

        $pattern = "/" . $type . "/i";

        foreach ($iterator as $filename) {

            if (preg_match($pattern, $filename->getBasename())) {

                unlink($filename->getPathname());
            }

        }

    }

    public function deleteWebP() {

        $dir = _EPH_PROD_IMG_DIR_;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_PROD_IMG_DIR_));

        $pattern = "/webP/i";

        foreach ($iterator as $filename) {

            if (preg_match($pattern, $filename->getBasename())) {

                unlink($filename->getPathname());
            }

        }

    }

    public function getImages() {

        $list = [];
        $singularTypes = [
            'img'       => ['dir' => _EPH_IMG_DIR_, 'iterate' => false],
            'theme_img' => ['dir' => _EPH_ROOT_DIR_ . $this->context->theme->img_theme, 'iterate' => false],
            'cms'       => ['dir' => _EPH_IMG_DIR_ . 'cms/', 'iterate' => false],
            'plugin'    => ['dir' => _EPH_PLUGIN_DIR_, 'iterate' => true],
            'store'     => ['dir' => _EPH_STORE_IMG_DIR_, 'iterate' => false],
        ];
        $extraTypes = $this->context->_hook->exec('actionImageManagerGetImages', [], null, true);

        if (is_array($extraTypes)) {

            foreach ($extraTypes as $plugin => $defs) {

                if (is_array($defs)) {

                    foreach ($defs as $key => $value) {
                        $singularTypes[$key] = $value;
                    }

                }

            }

        }

        foreach ($singularTypes as $key => $singularType) {
            $result = $this->getFolderImg($singularType['dir'], $singularType['iterate']);

            $list[$key]['todo'] = $result['todo'];
            $list[$key]['done'] = $result['done'];
            $list[$key]['total'] = count($result['done']) + count($result['todo']);
        }

        return $this->context->_tools->jsonEncode($list);

    }

    public function getFolderImg($folder, $iterate = false, $done = false) {

        $images = [];
        $dones = [];

        if ($iterate) {

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));

            foreach ($iterator as $filename) {

                if (preg_match('/\.(jpg|png|jpeg)$/', $filename->getBasename())) {
                    $fileTest = str_replace('.' . $filename->getExtension(), '.webp', $filename->getPathname());

                    if (file_exists($fileTest)) {
                        $dones[] = $filename->getPathname();
                    } else {
                        $images[] = $filename->getPathname();
                    }

                }

            }

        } else {
            $iterator = new AppendIterator();
            $iterator->append(new DirectoryIterator($folder));

            foreach ($iterator as $filename) {

                if (preg_match('/\.(jpg|png|jpeg)$/', $filename->getBasename())) {
                    $fileTest = str_replace('.' . $filename->getExtension(), '.webp', $filename->getPathname());

                    if (file_exists($fileTest)) {
                        $dones[] = $filename->getPathname();
                    } else {
                        $images[] = $filename->getPathname();
                    }

                }

            }

        }

        $return = [
            'done' => $dones,
            'todo' => $images,
        ];

        return $return;

    }

}
