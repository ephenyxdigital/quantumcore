<?php

class ComposerMedia extends PhenyxObjectModel {

    public $require_context = false;
    
    public $id_vc_media;
    public $file_name;
    public $mime;
	public $base_64;
    public $subdir;
    public $generated;
    public $legend;
    
    public $path;
    public $media;
    
    public static $definition = [
        'table'     => 'vc_media',
        'primary'   => 'id_vc_media',
        'multilang' => true,
        'fields'    => [
            'file_name' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'mime' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'base_64' => ['type' => self::TYPE_STRING, 'validate' => 'isString'], 
            'subdir'    => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'generated'                 => ['type' => self::TYPE_BOOL, 'lang' => true],
            'legend'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString'],
        ],
    ];
	
    public function __construct($id = null, $id_lang = null) {

        parent::__construct($id, $id_lang);
        
        if ($this->id) {
            if (str_contains($this->mime, 'image')) {
				$this->path = _COMPOSER_DIR_ .$this->subdir ;
                $this->media = $this->path.$this->file_name;
			} else if (str_contains($this->mime, 'pdf')) {
				$this->path = _BO_IMG_THEMES_DIR_ .$this->subdir;
                $this->media = $this->path.'media-downbload.png';
			}            
        } else {
            $this->path = _BO_IMG_THEMES_DIR_ ;
            $this->media = $this->path.'media-downbload.png';
        }
    }
	
	public function update($nullValues = false) {

		return parent::update(true);
	}
	
	public function delete($refresh = true) {

       if(file_exists(_PH_COMPOSER_DIR_. $this->subdir.$this->file_name)) {
		   unlink(_PH_COMPOSER_DIR_.$this->subdir.$this->file_name);
	   }

        return parent::delete();
    }
    
    public static function getIdMediaByName($fileName) {
       
        $types = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
			->select('name')
			->from('vc_image_type')
		);
        
        foreach($types as $type) {
            
            if(str_contains($fileName, $type['name'])) {
                
                $fileName = str_replace('-'.$type['name'], '', $fileName);
            }
        }       
        
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
			(new DbQuery())
				->select('id_vc_media, subdir')
				->from('vc_media')
				->where('`file_name` = \'' . pSQL($fileName) . '\'')
		);
    }
    
    public static function getPathMediaById($id) {
       
        $media = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
			(new DbQuery())
			->select('file_name, subdir')
			->from('vc_media')
            ->where('id_vc_media = '.$id)
		);
        
        return $media['subdir'].$media['file_name'];
        
        
    }
    
    public static function getPdfMedia($id_lang) {
        
        $context = Context::getContext();
        $cache = $context->cache_api;
        if($context->cache_enable && is_object($context->cache_api)) {
            $value = $cache->getData('getPdfMedia_' .$id_lang);
            $temp = empty($value) ? null : $value;
            if(!empty($temp)) {
                return $temp;
            }            
        }
               
        $result =  Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
			->select('vm.id_vc_media, vml.legend')
			->from('vc_media', 'vm')
            ->leftJoin('vc_media_lang', 'vml', 'vml.id_vc_media = vm.id_vc_media AND vml.id_lang = '.$id_lang)
			->where('vm.`mime` = \'application/pdf\'')
		);
        
        if($context->cache_enable && is_object($context->cache_api)) {
            $temp = $result === null ? null : PhenyxTool::getInstance()->jsonEncode($result);
            $cache->putData('getPdfMedia_' .$id_lang, $temp);
        }
        return $result;
    }

}