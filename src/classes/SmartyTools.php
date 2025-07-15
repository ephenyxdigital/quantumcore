<?php

/**
 * Class SmartyTools
 *
 * @since 1.9.1.0
 */
class SmartyTools {

   

    public static function getMemoryLimit() {

        $memory_limit = @ini_get('memory_limit');

        return Tools::getOctets($memory_limit);
    }

    public static function getOctets($option) {

        if (preg_match('/[0-9]+k/i', $option)) {
            return 1024 * (int) $option;
        }

        if (preg_match('/[0-9]+m/i', $option)) {
            return 1024 * 1024 * (int) $option;
        }

        if (preg_match('/[0-9]+g/i', $option)) {
            return 1024 * 1024 * 1024 * (int) $option;
        }

        return $option;
    }
    
    public static function isNumerice($foo) {
        
        if(!empty($foo)) {
            return is_numeric($foo);
        }
        
    }
    
    public static function rtrimString($str, $str_search) {

        $length_str = strlen($str_search);

        if (strlen($str) >= $length_str && substr($str, -$length_str) == $str_search) {
            $str = substr($str, 0, -$length_str);
        }

        return $str;
    }
    
    public static function arraySlice($array, $offset, $lenght = null, $preserve_keys = false) {
        
        if(is_array($array)) {
            return array_slice($array, $offset, $lenght, $preserve_keys);
        }
        
        return $array;
    }
    
    public static function arrayKeys($array) {
        
        if(is_array($array)) {
            return array_keys($array);
        }
        
        return $array;
    }

    public static function formatBytes($size, $precision = 2) {

        if (!$size) {
            return '0';
        }

        $base = log($size) / log(1024);
        $suffixes = ['', 'k', 'M', 'G', 'T'];

        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }

    public static function boolVal($value) {

        if (empty($value)) {
            $value = false;
        }

        return (bool) $value;
    }
    
    public static function isEmpty($string) {

        $value = false;
        if (empty($value)) {
            $value = true;
        }

        return (bool) $value;
    }
    
    public static function isString($string) {
        
        return is_string($string);
        
    }
    
     public static function varExport($array, $return = false) {
        
        if(is_array($array)) {
            return var_export($array, $return);
        }
        return null;
        
    }
    
    public static function isFloat($string) {
        
        return is_float($string);
        
    }
    
    public static function isInteger($string) {
        
        return is_int($string);
        
    }
    
    public static function initGet($string) {
        
        if(is_string($string)) {
            return ini_get($option);
        }
        return false;
        
    }
    
    public static function strReplace($field, $replace, $string) {
        
        if(is_string($string)) {
            return str_replace($field, $replace, $string);
        }
        
    }
    
    public static function isArray($str) {

        if (is_array($str)) {
            return true;
        }

        return false;
    }
    
    public static function isNull($string) {
        
        return is_null($string);
        
    }
    
    public static function isObject($object) {
        
        return is_object($object);
        
    }
    
    public static function isBool($string) {
        
        return is_bool($string);
        
    }
    
    public static function str_contains($search, $string) {
        
        return str_contains($string, $search);
    }
    
    public static function str_starts_with($search, $string) {
        
        return str_starts_with($string, $search);
    }
    
    public static function str_ends_with($search, $string) {
        
        return str_ends_with($string, $search);
    }
    
    public static function Rtrim($string, $char) {
        if(!is_null($string)) {
            return rtrim($string, $char);
        }
        return $string;
    }
    
    public static function build_date($args) {
        
        return date($args);
    }
    
    public static function smartyCount($array) {
        if(is_array($array)) {
            return count($array);
        }
        return null;
    }
    
    public static function addCslashes($string, $character) {
        
        return addcslashes($string, $characters);
    }
    
    public static function curRent($array) {
        return current($array);
    }
    
    public static function reSet($array) {
        return reset($array);
    }
    
    public static function printR($array) {
        if(is_array($array)) {
            return print_r($array);
        }
        return $array;
        
    }
    
    public static function inArray($string, $array) {
        if(is_array($array)) {
            return in_array($string, $array);
        }
        return $array;
        
    }
    
    public static function getAdminTranslation($string, $className) {
        
        $context = Context::getContext();
        if (!isset($context->phenyxConfig)) {
            $context->phenyxConfig = Configuration::getInstance();
            
        }
        if (!isset($context->company)) {
            $context->company = Company::initialize();
            
        }
        if (!isset($context->language)) {
            $context->language = self::jsonDecode(self::jsonEncode(Language::buildObject($context->phenyxConfig->get('EPH_LANG_DEFAULT')))); 
        }
        if (!isset($context->translations)) {

            $context->translations = new Translate($context->language->iso_code, $context->company);
        }
        return $context->translations->getAdminTranslation($string, $$className);
        
    }
    
    public static function jsonEncode($data, $encodeFlags = null) {

        if (is_null($encodeFlags)) {
            return json_encode($data);
        }

        return json_encode($data, $encodeFlags);
    }
    
    public static function jsonDecode($json, $assoc = false) {

        if (is_array($json)) {
            return $json;
        }

        if (is_null($assoc)) {

            if (!is_null($json)) {
                return json_decode($json);
            }

        }

        if (!is_null($json)) {
            return json_decode($json, $assoc);
        }

    }
    
    public static function arrayChunk($array, $length, $preserve_keys = false) {
        
        if(is_array($array)) {
            return array_chunk($array, $length, $preserve_keys);
        }
        return null;
        
    }
    
    public static function strTolower($string) {
        
        return strtolower($string);
    }
    
    public static function strStr($haystack, $needle, $before = false) {
        
        return strstr($haystack, $needle, $before);
    }
    
    public static function pregReplace($pattern, $replacement, $subject, $limit = -1, &$count = null) {
        
        return preg_replace($pattern, $replacement, $string, $limit, $count);
        
    }

    public static function intVal($value, $base = 10) {
        
        return intval($value, $base);               
    }
    
    public static function trimString($string) {
        
        if(!is_null($string)) {
            return trim($string);
        }
        return null;
    }
    
    public static function arrayValues($array) {
        
        if(is_array($array)) {
            return array_values($array);
        }
        return null;
        
    }
    
    public static function implodeArray($array, $args) {
        
        if(is_array($array)) {
            return implode($args, $array);
        }
    }
    
    public static function sizeOf($array) {
        if(is_array($array)) {
            return sizeof($array);
        }
        return null;
    }
    
    public static function sprinTf($string, $args) {
        
        return sprintf($string, $args);
    }
    
    public static function htmlEntities($string, $flags = ENT_QUOTES, $encoding = null, $double_encode = true) {
        
        return htmlentities($string, $flags, $encoding, $double_encode);
    }
    
    public static function ucFirst($str) {

        return ucfirst($str);
    }
    
    public static function arrayKeyExists($key, $array) {
        
        if(is_array($array)) {
            return array_key_exists($key, $array);
        }
        return false;
        
    }
    
    public static function explodes($args, $string) {
        
        if(is_string($string)) {
            return explode($args, $string);
        }
        return $string;
        
    }
    
    public static function fileExists($path) {
        
        return file_exists($path);
    }
    
     public static function minifyHTML($htmlContent) {

        if (trim($htmlContent) === "") {
            return $htmlContent;
        }

        // Remove extra white-space(s) between HTML attribute(s)
        $htmlContent = preg_replace_callback('#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s', function ($matches) {

            return '<' . $matches[1] . preg_replace('#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s', ' $1$2', $matches[2]) . $matches[3] . '>';
        }, str_replace("\r", "", $htmlContent));
        // Minify inline CSS declaration(s)

        if (strpos($htmlContent, ' style=') !== false) {
            $htmlContent = preg_replace_callback('#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s', function ($matches) {

                return '<' . $matches[1] . ' style=' . $matches[2] . self::minify_css($matches[3]) . $matches[2];
            }, $htmlContent);
        }

        if (strpos($htmlContent, '</style>') !== false) {
            $htmlContent = preg_replace_callback('#<style(.*?)>(.*?)</style>#is', function ($matches) {

                return '<style' . $matches[1] . '>' . self::minify_css($matches[2]) . '</style>';
            }, $htmlContent);
        }

        if (strpos($htmlContent, '</script>') !== false) {
            $htmlContent = preg_replace_callback('#<script(.*?)>(.*?)</script>#is', function ($matches) {

                return '<script' . $matches[1] . '>' . self::minify_js($matches[2]) . '</script>';
            }, $input);
        }

        return preg_replace(
            [
                // t = text
                // o = tag open
                // c = tag close
                // Keep important white-space(s) after self-closing HTML tag(s)
                '#<(img|input)(>| .*?>)#s',
                // Remove a line break and two or more white-space(s) between tag(s)
                '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
                '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s', // t+c || o+t
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s', // o+o || c+c
                '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s', // c+t || t+o || o+t -- separated by long white-space(s)
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s', // empty tag
                '#<(img|input)(>| .*?>)<\/\1>#s', // reset previous fix
                '#(&nbsp;)&nbsp;(?![<\s])#', // clean up ...
                '#(?<=\>)(&nbsp;)(?=\<)#', // --ibid
                // Remove HTML comment(s) except IE comment(s)
                '#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s',
            ],
            [
                '<$1$2<$1>',
                '$1$2$3',
                '$1$2$3',
                '$1$2$3$4$5',
                '$1$2$3$4$5$6$7',
                '$1$2$3',
                '<$1$2',
                '$1 ',
                '$1',
                "",
            ],
            $htmlContent);
    }
    
    
    public static function minify_css($input) {

        if (trim($input) === "") {
            return $input;
        }

        return preg_replace(
            [
                // Remove comment(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
                // Remove unused white-space(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
                // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
                '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
                // Replace `:0 0 0 0` with `:0`
                '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
                // Replace `background-position:0` with `background-position:0 0`
                '#(background-position):0(?=[;\}])#si',
                // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
                '#(?<=[\s:,\-])0+\.(\d+)#s',
                // Minify string value
                '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
                '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
                // Minify HEX color code
                '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
                // Replace `(border|outline):none` with `(border|outline):0`
                '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
                // Remove empty selector(s)
                '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s',
            ],
            [
                '$1',
                '$1$2$3$4$5$6$7',
                '$1',
                ':0',
                '$1:0 0',
                '.$1',
                '$1$3',
                '$1$2$4$5',
                '$1$2$3',
                '$1:0',
                '$1$2',
            ],
            $input);
    }
    
    public static function mdString($string) {
        
        if(is_string($string)) {
            return md5($string);
        }
        return null;
        
    }
    
    public static function timeToSeconds(string $time) {
        $arr = explode(':', $time);
        if (count($arr) === 3) {
            return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
        }
        return $arr[0] * 60 + $arr[1];
    }
    


    public static function minify_js($input) {

        if (trim($input) === "") {
            return $input;
        }

        return preg_replace(
            [
                // Remove comment(s)
                '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
                // Remove white-space(s) outside the string and regex
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
                // Remove the last semicolon
                '#;+\}#',
                // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
                '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
                // --ibid. From `foo['bar']` to `foo.bar`
                '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i',
            ],
            [
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3',
            ],
            $input);
    }
    
    public static function getFirstArrayKey($array) {
        
        if(is_array($array)) {
            return array_key_first($array);
        }
        return null;
        
    }


  
}
