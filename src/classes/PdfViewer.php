<?php

/**
 * Class PdfViewer
 *
 * @since 2.1.0.0
 */
class PdfViewer {

    public $context;

    public $name;

    public $pages;

    public $tableOfContent;

    public $tableOfContentCloseOnClick = 1;

    public $thumbsCloseOnClick = 1;

    public $deeplinkingEnabled = 0;

    public $assets;

    public $pdfUrl;

    public $pdfBrowserViewerIfMobile = 0;

    public $pdfBrowserViewerIfIE = "false";
    public $pdfBrowserViewerFullscreen = "true";
    public $pdfBrowserViewerFullscreenTarget = "\"_blank\"";
    public $rangeChunkSize = 64;
    public $disableRange = "false";
    public $disableStream = "true";
    public $disableAutoFetch = "true";
    public $pdfAutoLinks = "false";

    public $htmlLayer = "true"; // to implement

    public $rightToLeft = "false";

    //page that will be displayed when the book starts
    public $startPage = 0;

    //if the sound is enabled
    public $sound = "true";

    public $backgroundColor = "\"rgb(81, 85, 88)\"";
    public $backgroundImage = "";
    public $backgroundPattern = "";
    public $backgroundTransparent = "false";

    //book default settings
    public $thumbSize = 130;

    public $loadAllPages = "false";
    public $loadPagesF = 2;
    public $loadPagesB = 1;

    public $autoplayOnStart = "false";
    public $autoplayInterval = 3000;
    public $autoplayLoop = "true";

    //UI settings

    public $skin = "\"dark\""; //"dark"; "light"; "gradient"
    public $layout = "1"; //"1"; "2"; "3"; "4"

    public $menuOverBook = "false";
    public $menuFloating = "false";
    public $menuBackground = '';
    public $menuShadow = '';
    public $menuMargin = 0;
    public $menuPadding = 0;
    public $menuTransparent = "false";

    public $menu2OverBook = "true";
    public $menu2Floating = "false";
    public $menu2Background = '';
    public $menu2Shadow = '';
    public $menu2Margin = 0;
    public $menu2Padding = 0;
    public $menu2Transparent = "true";

    public $icons;

    public $skinColor = '';
    public $skinBackground = '';

    // menu buttons
    public $btnColor = '';
    public $btnBackground = '\'none\'';
    public $btnSize = 14;
    public $btnRadius = 2;
    public $btnMargin = 2;
    public $btnPaddingV = 10;
    public $btnPaddingH = 10;
    public $btnShadow = '';
    public $btnTextShadow = '';
    public $btnBorder = '';
    public $btnColorHover = "";
    public $btnBackgroundHover = '';

    //side navigation arrows
    public $sideBtnColor = '\'#FFF\'';
    public $sideBtnBackground = '\'#00000033\'';
    public $sideBtnSize = 30;
    public $sideBtnRadius = 0;
    public $sideBtnMargin = 0;
    public $sideBtnPaddingV = 5;
    public $sideBtnPaddingH = 0;
    public $sideBtnShadow = '';
    public $sideBtnTextShadow = '';
    public $sideBtnBorder = '';
    public $sideBtnColorHover = "\"#FFF\"";
    public $sideBtnBackgroundHover = '\'#00000066\'';

    // menu buttons on transparent menu
    public $floatingBtnColor = "\"#EEE\"";
    public $floatingBtnColorHover = "";
    public $floatingBtnBackground = "\"#00000044\"";
    public $floatingBtnBackgroundHover = '';
    public $floatingBtnSize = '\'null\'';
    public $floatingBtnRadius = '\'null\'';
    public $floatingBtnMargin = '\'null\'';
    public $floatingBtnPadding = '\'null\'';
    public $floatingBtnShadow = '';
    public $floatingBtnTextShadow = '';
    public $floatingBtnBorder = '';

    public $hasTimer = "false";

    public $currentPage = [
        'enabled'    => "true",
        'title'      => "\"Current page\"",
        'vAlign'     => '\'top\'',
        'hAlign'     => '\'left\'',
        'marginH'    => 0,
        'marginV'    => 0,
        'color'      => '\'\'',
        'background' => '\'\'',
    ];

    public $btnFirst = [
        'enabled' => "false",
        'title'   => "\"First page",
        'iconFA'  => "\"flipbook-icon-angle-double-left\"",
        'iconM'   => "\"flipbook-icon-first_page\"",
    ];

    public $btnPrev = [
        'enabled' => "true",
        'title'   => "\"Previous page\"",
        'iconFA'  => "\"flipbook-icon-angle-left\"",
        'iconM'   => "\"flipbook-icon-keyboard_arrow_left\"",
    ];

    public $btnNext = [
        'enabled' => "true",
        'title'   => "\"Next page\"",
        'iconFA'  => "\"flipbook-icon-angle-right\"",
        'iconM'   => "\"flipbook-icon-keyboard_arrow_right\"",
    ];

    public $btnLast = [
        'enabled' => "false",
        'title'   => "\"Last page\"",
        'iconFA'  => "\"flipbook-icon-angle-double-right\"",
        'iconM'   => "\"flipbook-icon-last_page\"",
    ];

    public $btnZoomIn = [
        'enabled' => "true",
        'title'   => "\"Zoom in\"",
        'iconFA'  => "\"flipbook-icon-plus\"",
        'iconM'   => "\"flipbook-icon-add\"",
    ];

    public $btnZoomOut = [
        'enabled' => "true",
        'title'   => "\"Zoom out\"",
        'iconFA'  => "\"flipbook-icon-minus\"",
        'iconM'   => "\"flipbook-icon-remove1\"",
    ];

    public $btnRotateLeft = [
        'enabled' => "false",
        'title'   => "\"Rotate left\"",
        'iconFA'  => "\"flipbook-icon--undo\"",
    ];

    public $btnRotateRight = [
        'enabled' => "false",
        'title'   => "\"Rotate right\"",
        'iconFA'  => "\"flipbook-icon--redo\"",
    ];

    public $btnAutoplay = [
        'enabled'    => "true",
        'title'      => "\"Autoplay\"",
        'iconFA'     => "\"flipbook-icon-play\"",
        'iconM'      => "\"flipbook-icon-play_arrow\"",
        'iconFA_alt' => "\"flipbook-icon-pause\"",
        'iconM_alt'  => "\"flipbook-icon-pause1\"",
    ];

    public $btnSearch = [
        'enabled' => "false",
        'title'   => "\"Search\"",
        'iconFA'  => "\"flipbook-icon-search\"",
        'iconM'   => "\"flipbook-icon-search1\"",
    ];

    public $btnSelect = [
        'enabled' => "true",
        'title'   => "\"Select tool\"",
        'iconFA'  => "\"flipbook-icon-i-cursor\"",
        'iconM'   => "\"flipbook-icon-text_format\"",
    ];

    public $btnBookmark = [
        'enabled' => "true",
        'title'   => "\"Bookmark\"",
        'iconFA'  => "\"flipbook-icon-bookmark\"",
        'iconM'   => "\"flipbook-icon-bookmark1\"",
    ];

    public $btnNotes = [
        'enabled' => "false",
        'title'   => "\"Notes\"",
        'iconFA'  => "\"flipbook-icon-comment\"",
        'iconM'   => "\"flipbook-icon-chat_bubble\"",
    ];

    public $btnToc = [
        'enabled' => "true",
        'title'   => "\"Table of Contents\"",
        'iconFA'  => "\"flipbook-icon-list-ol\"",
        'iconM'   => "\"flipbook-icon-toc\"",
    ];

    public $btnThumbs = [
        'enabled' => "true",
        'title'   => "\"Pages\"",
        'iconFA'  => "\"flipbook-icon-th-large\"",
        'iconM'   => "\"flipbook-icon-view_module\"",
    ];

    public $btnShare = [
        'enabled'      => "true",
        'title'        => "\"Share\"",
        'iconFA'       => "\"flipbook-icon-share-alt\"",
        'iconM'        => "\"flipbook-icon-share1\"",
        'hideOnMobile' => "true",
    ];

    public $btnPrint = [
        'enabled'      => "true",
        'title'        => "\"Print\"",
        'iconFA'       => "\"flipbook-icon-print\"",
        'iconM'        => "\"flipbook-icon-local_printshop\"",
        'hideOnMobile' => "true",
    ];

    public $btnDownloadPages = [
        'enabled' => "true",
        'title'   => "\"Download pages\"",
        'iconFA'  => "\"flipbook-icon-download\"",
        'iconM'   => "\"flipbook-icon-file_download\"",
        'url'     => "\"images/pages.zip\"",
        'name'    => "\"allPages.zip\"",
    ];

    public $btnDownloadPdf = [
        'forceDownload'   => "false",
        'enabled'         => "\"true\"",
        'title'           => "\"Download PDF\"",
        'iconFA'          => "\"flipbook-icon-file\"",
        'iconM'           => "\"flipbook-icon-picture_as_pdf\"",
        'url'             => '\'null\'',
        'openInNewWindow' => "true",
        'name'            => "\"allPages.pdf\"",
    ];

    public $btnSound = [
        'enabled'      => "true",
        'title'        => "\"Volume\"",
        'iconFA'       => "\"flipbook-icon-volume-up\"",
        'iconFA_alt'   => "\"flipbook-icon-volume-off\"",
        'iconM'        => "\"flipbook-icon-volume_up\"",
        'iconM_alt'    => "\"flipbook-icon-volume_mute\"",
        'hideOnMobile' => "true",
    ];

    public $btnExpand = [
        'enabled'    => "true",
        'title'      => "\"Toggle fullscreen\"",
        'iconFA'     => "\"flipbook-icon-expand\"",
        'iconM'      => "\"flipbook-icon-fullscreen\"",
        'iconFA_alt' => "\"flipbook-icon-compress\"",
        'iconM_alt'  => "\"flipbook-icon-fullscreen_exit\"",
    ];

    public $btnClose = [
        'title'  => "\"Close",
        'iconFA' => "\"flipbook-icon-times\"",
        'iconM'  => "\"flipbook-icon-clear\"",
        'hAlign' => '\'right\'',
        'vAlign' => '\'top\'',
        'size'   => 20,
    ];

    public $btnShareIfMobile = "false";
    public $btnSoundIfMobile = "false";
    public $btnPrintIfMobile = "false";

    public $buttons;

    public $sideNavigationButtons = "true";

    public $hideMenu = "false";

    //share
    public $shareUrl = '\'null\'';
    public $shareTitle = '\'null\'';
    public $shareImage = '\'null\'';

    public $whatsapp = [
        'enabled' => "true",
        'icon'    => '\'flipbook-icon-whatsapp\'',
    ];

    public $twitter = [
        'enabled' => "true",
        'icon'    => '\'flipbook-icon-twitter\'',
    ];

    public $facebook = [
        'enabled' => "true",
        'icon'    => '\'flipbook-icon-facebook\'',
    ];

    public $pinterest = [
        'enabled' => "true",
        'icon'    => '\'flipbook-icon-pinterest-p\'',
    ];

    public $email = [
        'enabled' => "true",
        'icon'    => '\'flipbook-icon-envelope\'',
    ];

    public $linkedin = [
        'enabled' => "true",
        'icon'    => '\'flipbook-icon-linkedin\'',
    ];

    public $digg = [
        'enabled' => "false",
        'icon'    => '\'flipbook-icon-digg\'',
    ];

    public $reddit = [
        'enabled' => "false",
        'icon'    => '\'flipbook-icon-reddit-alien\'',
    ];

    public $pdf = [
        'annotationLayer' => "false",
    ];

    public $pageTextureSize = 2048;
    public $pageTextureSizeSmall = 1500;
    public $thumbTextureSize = 300;

    public $pageTextureSizeMobile = 1500;
    public $pageTextureSizeMobileSmall = 1024;

    //flip animation type; can be "2d"; "3d" ; "webgl"; "swipe"
    public $viewMode = '\'3d\'';
    public $singlePageMode = "false";
    public $singlePageModeIfMobile = "false";
    public $zoomMin = .95;
    public $zoomMax2 = '\'null\'';

    public $zoomSize = '\'null\'';
    public $zoomStep = 2;
    public $zoomTime = 300;
    public $zoomReset = "false";
    public $zoomResetTime = 300;

    public $wheelDisabledNotFullscreen = "false";
    public $arrowsDisabledNotFullscreen = "false";
    public $arrowsAlwaysEnabledForNavigation = "true";
    public $touchSwipeEnabled = "true";

    public $responsiveView = "true";
    public $responsiveViewRatio = 1; // use responsive view only in portrait mode
    public $responsiveViewTreshold = 768;
    public $minPixelRatio = 1; //between 1 and 2; 1.5 = best ratio performance FPS / image quality

    public $pageFlipDuration = 1;

    public $contentOnStart = "false";
    public $thumbnailsOnStart = "false";
    public $searchOnStart = "false";

    public $sideMenuOverBook = "true";
    public $sideMenuOverMenu = "false";
    public $sideMenuOverMenu2 = "true";
    public $sideMenuPosition = '\'left\'';

    //lightbox settings

    public $lightBox = "false";
    public $lightBoxOpened = "false";
    public $lightBoxFullscreen = "false";
    public $lightboxCloseOnClick = "false";
    public $lightboxResetOnOpen = "true";
    public $lightboxBackground = '\'null\'';
    public $lightboxBackgroundColor = '\'null\'';
    public $lightboxBackgroundPattern = '\'null\'';
    public $lightboxBackgroundImage = '\'null\'';
    public $lightboxStartPage = '\'null\'';
    public $lightboxMarginV = '0';
    public $lightboxMarginH = '0';
    public $lightboxCSS = '';
    public $lightboxPreload = "false";
    public $lightboxShowMenu = "false"; // show menu while book is loading so lightbox can be closed
    public $lightboxCloseOnBack = "true";

    // WebGL settings

    public $disableImageResize = "true"; //disable image resize to power of 2 (needed for anisotropic filtering)

    public $pan = 0;
    public $panMax = 10;
    public $panMax2 = 2;
    public $panMin = -10;
    public $panMin2 = -2;
    public $tilt = 0;
    public $tiltMax = 0;
    public $tiltMax2 = 0;
    public $tiltMin = -20;
    public $tiltMin2 = -5;

    public $rotateCameraOnMouseMove = "false";
    public $rotateCameraOnMouseDrag = "true";

    public $lights = "true";
    public $lightColor = 0xFFFFFF;
    public $lightPositionX = 0;
    public $lightPositionZ = 1400;
    public $lightPositionY = 350;
    public $lightIntensity = .6;

    public $shadows = "true";
    public $shadowMapSize = 1024;
    public $shadowOpacity = .2;
    public $shadowDistance = 0;

    public $pageRoughness = 1;
    public $pageMetalness = 0;

    public $pageHardness = 2;
    public $coverHardness = 2;
    public $pageSegmentsW = 10;
    public $pageSegmentsH = 1;

    public $pageMiddleShadowSize = 2;
    public $pageMiddleShadowColorL = "\"#999999\"";
    public $pageMiddleShadowColorR = "\"#777777\"";

    public $antialias = "false";

    // logo

    public $logoImg = ''; //url of logo image
    public $logoUrl = ''; // url target
    public $logoCSS = '\'position:absolute;\'';
    public $logoHideOnMobile = "false";

    public $printMenu = "true";
    public $downloadMenu = "true";

    public $cover = "true";
    public $backCover = "true";

    public $pdfTextLayer = "true";
    public $textLayer = "false";
    public $annotationLayer = "true";

    public $googleAnalyticsTrackingCode = '\'null\'';

    public $minimumAndroidVersion = 6;

    public $linkColor = '\'rgba(0, 0, 0, 0)\'';
    public $linkColorHover = '\'rgba(255, 255, 0, 1)\'';
    public $linkOpacity = 0.4;
    public $linkTarget = '\'_blank\''; // _blank - new window;  _self - same window

    public $rightClickEnabled = "true";

    public $pageNumberOffset = 0; // to start book page count at different page;  example Cover;  1;  2;  ... -> pageNumberOffset = 1

    public $flipSound = "true";
    public $backgroundMusic = "false";
    public $doubleClickZoomDisabled = "false";
    public $pageDragDisabled = "false";
    public $pageClickAreaWdith = '\'10%\''; // width of the page that behaves like next / previous page button

    public $BtnPrintCourse = "false";

    public $strings = [];

    //mobile devices settings - override any setting for mobile devices
    public $mobile = [

        'shadows'       => "false",
        'pageSegmentsW' => 5,

    ];

    public $extraVars = [];

    public $declared = [];

    public $target;

    public $builder = [];

    public function __construct($target, $extraVars = []) {

        $this->context = Context::getContext();

        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();

        }

        if (!isset($this->context->company)) {
            $this->context->company = Company::initialize();

        }

        if (!isset($this->context->language)) {
            $this->context->language = Tools::jsonDecode(Tools::jsonEncode(Language::buildObject($this->context->phenyxConfig->get('EPH_LANG_DEFAULT'))));
        }

        if (!isset($this->context->translations)) {

            $this->context->translations = new Translate($this->context->language->iso_code, $this->context->company);
        }

        $this->target = $target;
        $this->extraVars = $extraVars;
        $this->strings = [
            'print'                => '\'' . $this->l('Print') . '\'',
            'printLeftPage'        => '\'' . $this->l('Print left page') . '\'',
            'printRightPage'       => '\'' . $this->l('Print right page') . '\'',
            'printCurrentPage'     => '\'' . $this->l('Print current page') . '\'',
            'printAllPages'        => '\'' . $this->l('Print all pages') . '\'',

            'download'             => '\'' . $this->l('Download') . '\'',
            'downloadLeftPage'     => '\'' . $this->l('Download left page') . '\'',
            'downloadRightPage'    => '\'' . $this->l('Download right page') . '\'',
            'downloadCurrentPage'  => '\'' . $this->l('Download current page') . '\'',
            'downloadAllPages'     => '\'' . $this->l('Download all pages') . '\'',

            'bookmarks'            => '\'' . $this->l('Bookmarks') . '\'',
            'bookmarkLeftPage'     => '\'' . $this->l('Bookmark left page') . '\'',
            'bookmarkRightPage'    => '\'' . $this->l('Bookmark right page') . '\'',
            'bookmarkCurrentPage'  => '\'' . $this->l('Bookmark current page') . '\'',

            'search'               => '\'' . $this->l('Search') . '\'',
            'findInDocument'       => '\'' . $this->l('Find in document') . '\'',
            'pagesFoundContaining' => '\'' . $this->l('pages found containing') . '\'',
            'noMatches'            => '\'' . $this->l('No matches') . '\'',
            'matchesFound'         => '\'' . $this->l('matches found') . '\'',
            'page'                 => '\'' . $this->l('Page') . '\'',
            'matches'              => '\'' . $this->l('matches') . '\'',

            'thumbnails'           => '\'' . $this->l('Thumbnails') . '\'',
            'tableOfContent'       => '\'' . $this->l('Table of Contents') . '\'',
            'share'                => '\'' . $this->l('Share') . '\'',
            'notes'                => '\'' . $this->l('Notes') . '\'',

            'pressEscToClose'      => '\'' . $this->l('Press ESC to close') . '\'',

            'password'             => '\'' . $this->l('Password') . '\'',
            'addNote'              => '\'' . $this->l('Add note') . '\'',
            'typeInYourNote'       => '\'' . $this->l('Type in your note...') . '\'',

        ];
        $this->currentPage['title'] = '\'' . $this->l('Current page') . '\'';
        $this->btnFirst['title'] = '\'' . $this->l('First page') . '\'';
        $this->btnPrev['title'] = '\'' . $this->l('Previous page') . '\'';
        $this->btnNext['title'] = '\'' . $this->l('Next page') . '\'';
        $this->btnLast['title'] = '\'' . $this->l('Last page') . '\'';
        $this->btnZoomIn['title'] = '\'' . $this->l('Zoom in') . '\'';
        $this->btnZoomOut['title'] = '\'' . $this->l('Zoom out') . '\'';
        $this->btnRotateLeft['title'] = '\'' . $this->l('Rotate left') . '\'';
        $this->btnRotateRight['title'] = '\'' . $this->l('Rotate right') . '\'';
        $this->btnAutoplay['title'] = '\'' . $this->l('Autoplay') . '\'';
        $this->btnSearch['title'] = '\'' . $this->l('Search') . '\'';
        $this->btnSelect['title'] = '\'' . $this->l('Select tool') . '\'';
        $this->btnBookmark['title'] = '\'' . $this->l('Bookmark') . '\'';
        $this->btnToc['title'] = '\'' . $this->l('Table of Contents') . '\'';
        $this->btnThumbs['title'] = '\'' . $this->l('Pages') . '\'';
        $this->btnShare['title'] = '\'' . $this->l('Share') . '\'';
        $this->btnPrint['title'] = '\'' . $this->l('Print') . '\'';
        $this->btnDownloadPages['title'] = '\'' . $this->l('Download pages') . '\'';
        $this->btnDownloadPdf['title'] = '\'' . $this->l('Download PDF') . '\'';
        $this->btnSound['title'] = '\'' . $this->l('Volume') . '\'';
        $this->btnExpand['title'] = '\'' . $this->l('Toggle fullscreen') . '\'';
        $this->btnClose['title'] = '\'' . $this->l('Close') . '\'';

    }

    public function generatePdfViewerOption() {

        foreach ($this as $key => $value) {

            if ($key == 'context' || $key == 'target' || $key == 'builder') {
                continue;
            }

            if (!empty($value)) {
                $this->builder[$key] = $value;

            }

        }

    }

    public function buildViewerScript() {

        $html = '<script type="text/javascript">' . PHP_EOL;

        if (count($this->extraVars)) {

            foreach ($this->extraVars as $key => $value) {
                $html .= "\t" . 'var ' . $key . ' = "' . $value . '";' . PHP_EOL;
            }

        }

        $html .= PHP_EOL . "\t" . '$(document).ready(function () {' . PHP_EOL;
        $html .= "\t" . "\t" . '$("#' . $this->target . '").flipBook({' . PHP_EOL;

        foreach ($this->builder as $key => $value) {

            if (is_array($value)) {
                $html .= "\t" . "\t" . "\t" . $this->deployArrayScript($key, $value) . PHP_EOL;
            } else {
                $html .= "\t" . "\t" . "\t" . $key . ' :' . $value
                    . ',' . PHP_EOL;
            }

        }

        $html .= "\t" . "\t" . '});' . PHP_EOL;
        $html .= "\t" . '})' . PHP_EOL;
        $html .= '</script>' . PHP_EOL;

        return $html;

    }

    public function deployArrayScript($option, $value, $sub = false) {

        if ($sub) {

            if (is_string($option) && is_array($value) && !Tools::is_assoc($value)) {
                $jsScript = $option . ': [' . PHP_EOL;

                foreach ($value as $suboption => $value) {

                    if (is_array($value)) {
                        $jsScript .= "\t" . "\t" . "\t" . "\t" . $this->deployArrayScript($suboption, $value, true);
                    } else

                    if (is_string($suboption)) {
                        $jsScript .= "\t" . "\t" . "\t" . "\t" . $suboption . ': ' . $value . ',' . PHP_EOL;
                    } else {
                        $jsScript .= "\t" . "\t" . "\t" . "\t" . $value . ',' . PHP_EOL;
                    }

                }

                $jsScript .= '          ],' . PHP_EOL;
                return $jsScript;

            } else {

                if (is_string($option)) {
                    $jsScript = $option . ': {' . PHP_EOL;
                } else {
                    $jsScript = ' {' . PHP_EOL;
                }

            }

        } else {

            if (is_string($option)) {
                $jsScript = $option . ': {' . PHP_EOL;
            } else {
                $jsScript = ' {' . PHP_EOL;
            }

        }

        foreach ($value as $suboption => $value) {

            if (is_array($value)) {
                $jsScript .= "\t" . "\t" . "\t" . "\t" . $this->deployArrayScript($suboption, $value, true);
            } else

            if (is_string($suboption)) {
                $jsScript .= "\t" . "\t" . "\t" . "\t" . $suboption . ' :' . $value . ',' . PHP_EOL;
            } else {
                $jsScript .= "\t" . "\t" . "\t" . "\t" . $value . ',' . PHP_EOL;
            }

        }

        if ($sub) {
            $jsScript .= "\t" . "\t" . "\t" . '},' . PHP_EOL;
        } else {
            $jsScript .= "\t" . "\t" . "\t" . '},' . PHP_EOL;
        }

        return $jsScript;

    }

    public function l($string, $idLang = null, $context = null) {

        $class = 'PdfViewer';

        if (isset($this->context->translations)) {
            return $this->context->translations->getClassTranslation($string, $class);
        }

        return $string;

    }

}
