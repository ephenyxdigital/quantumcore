<?php

class GsiteMap extends PhenyxObjectModel {

	protected static $instance;

	protected $rb_file = '';
	protected $rb_data = [];
	protected $sm_file = '';

	const HOOK_ADD_URLS = 'gSitemapAppendUrls';

	public $id_lang;

	public $link;

	public $cron = false;

	public $type_array = [];

	public $smartyAssign = [];

	public $disable_link = [];

	public static $definition = [
		'table'   => 'gsitemap',
		'primary' => 'id_gsitemap',
		'fields'  => [
			'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false],
			'link'    => ['type' => self::TYPE_STRING],
		],
	];

	public function __construct($id = null) {

		parent::__construct($id);

		$this->disable_link = !empty($this->context->phenyxConfig->get('GSITEMAP_DISABLE_LINKS')) ? explode(', ', $this->context->phenyxConfig->get('GSITEMAP_DISABLE_LINKS')) : [];

		$this->type_array = ['home', 'meta', 'cms', 'plugin'];
		$sitemapTypes = $this->context->_hook->exec('actionGetSiteMapType', ['type_array' => $this->type_array], null, true);

		$this->rb_file = _EPH_ROOT_DIR_ . '/robots.txt';
		$this->rb_data = $this->getRobotsContent();
		$this->sm_file = _EPH_ROOT_DIR_ . DIRECTORY_SEPARATOR . $this->context->language->id . '_index_sitemap.xml';

		if (is_array($sitemapTypes)) {

			foreach ($sitemapTypes as $plugin => $values) {

				if (is_array($values)) {

					foreach ($values as $value) {
						$this->type_array[] = $value;
					}

				} else

				if (!empty($values) && is_string($values)) {
					$this->type_array[] = $values;
				}

			}

		}

		$metas = $this->getSiteMapMetas();

		foreach ($metas as $meta) {

			if (in_array($meta['id_meta'], $this->disable_link)) {

				if (($key = array_search($meta['page'], $this->type_array)) !== false) {
					unset($this->type_array[$key]);
				}

			}

		}

	}

	public static function getInstance($id = null, $idLang = null) {

		if (!GsiteMap::$instance) {
			GsiteMap::$instance = new GsiteMap($id, $idLang);
		}

		return GsiteMap::$instance;
	}

	public function getSiteMapMetas() {

		Meta::cleanPluginMeta();

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('*')
				->from('meta')
				->where('`controller` LIKE "front"')
				->where('`configurable` = 1')
				->orderBy('id_meta ASC')
		);
	}

	protected function normalizeDirectory($directory) {

		$last = $directory[strlen($directory) - 1];

		if (in_array($last, ['/', '\\'])) {
			$directory[strlen($directory) - 1] = DIRECTORY_SEPARATOR;
			return $directory;
		}

		$directory .= DIRECTORY_SEPARATOR;
		return $directory;
	}

	public function emptySitemap() {

		$links = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('*')
				->from('gsitemap')
				->where('`id_lang` = ' . (int) $this->context->language->id)
		);

		if ($links) {

			foreach ($links as $link) {
				@unlink($this->normalizeDirectory(_EPH_ROOT_DIR_) . $link['link']);
			}

			return Db::getInstance()->execute(
				(new DbQuery())
					->type('DELETE')
					->from('gsitemap')
					->where('`id_lang` = ' . (int) $this->context->language->id)
			);

		}

		return true;
	}

	protected function _recursiveSitemapCreator($link_sitemap, $lang, &$index) {

		if (!count($link_sitemap)) {
			return false;
		}

		$sitemap_link = $lang . '_sitemap.xml';
		$write_fd = fopen($this->normalizeDirectory(_EPH_ROOT_DIR_) . $sitemap_link, 'w');

		fwrite($write_fd, '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\r\n");

		foreach ($link_sitemap as $key => $file) {
			fwrite($write_fd, '<url>' . "\r\n");
			$lastmod = (isset($file['lastmod']) && !empty($file['lastmod'])) ? date('c', strtotime($file['lastmod'])) : null;
			$this->_addSitemapNode($write_fd, htmlspecialchars(strip_tags($file['link'])), $this->_getPriorityPage($file['page']), $this->context->phenyxConfig->get('GSITEMAP_FREQUENCY'), $lastmod);

			if ($file['image']) {
				$this->_addSitemapNodeImage(
					$write_fd, htmlspecialchars(strip_tags($file['image']['link'])), isset($file['image']['title_img']) ? htmlspecialchars(
						str_replace(
							[
								"\r\n",
								"\r",
								"\n",
							], '', strip_tags($file['image']['title_img'])
						)
					) : '', isset($file['image']['caption']) ? htmlspecialchars(
						str_replace(
							[
								"\r\n",
								"\r",
								"\n",
							], '', strip_tags($file['image']['caption'])
						)
					) : ''
				);
			}

			fwrite($write_fd, '</url>' . "\r\n");
		}

		fwrite($write_fd, '</urlset>' . "\r\n");
		fclose($write_fd);
		$this->_saveSitemapLink($sitemap_link);
		$index++;

		return true;
	}

	public function createSitemap() {

		if (@fopen($this->normalizeDirectory(_EPH_ROOT_DIR_) . '/test.txt', 'w') == false) {
			$this->context->smarty->assign('google_maps_error', $this->la('An error occured while trying to check your file permissions. Please adjust your permissions to allow PhenyxShop to write a file in your root directory.'));

			return false;
		} else {
			@unlink($this->normalizeDirectory(_EPH_ROOT_DIR_) . 'test.txt');
		}

		$type = Tools::getValue('type') ? Tools::getValue('type') : '';
		$languages = Language::getLanguages(true);
		$lang_stop = Tools::getValue('lang') ? true : false;
		$id_obj = Tools::getValue('id') ? (int) Tools::getValue('id') : 0;

		foreach ($languages as $lang) {
			$i = 0;
			$index = (Tools::getValue('index') && Tools::getValue('lang') == $lang['iso_code']) ? (int) Tools::getValue('index') : 0;

			if ($lang_stop && $lang['iso_code'] != Tools::getValue('lang')) {
				continue;
			} else

			if ($lang_stop && $lang['iso_code'] == Tools::getValue('lang')) {
				$lang_stop = false;
			}

			$link_sitemap = [];

			foreach ($this->type_array as $type_val) {

				if ($type == '' || $type == $type_val) {

					if (method_exists($this, '_get' . ucfirst($type_val) . 'Link')) {

						if (!$this->{'_get' . ucfirst($type_val) . 'Link'}

							($link_sitemap, $lang, $index, $i, $id_obj)) {
							return false;
						}

						$type = '';
						$id_obj = 0;
					} else {
						$linkToSitemaps = $this->context->_hook->exec('addLinkToSitemap', ['type_val' => $type_val, 'lang' => $lang, 'id_obj' => $id_obj], null, true);

						if (!is_null($linkToSitemaps) && is_array($linkToSitemaps)) {

							foreach ($linkToSitemaps as $plugin => $values) {

								if (is_array($values)) {

									foreach ($values as $key => $value) {
										$link_sitemap[] = $value;

									}

								}

							}

						}

					}

				}

			}

			$this->_recursiveSitemapCreator($link_sitemap, $lang['iso_code'], $index);
			$page = '';
			$index = 0;
		}

		$this->_createIndexSitemap();
		$this->context->phenyxConfig->updateValue('GSITEMAP_LAST_EXPORT', date('r'));
		Tools::file_get_contents('http://www.google.com/webmasters/sitemaps/ping?sitemap=' . urlencode('http' . ($this->context->phenyxConfig->get('EPH_SSL_ENABLED') ? 's' : '') . '://' . Tools::getDomain(false, true) . $this->context->company->physical_uri . $this->context->company->virtual_uri . $this->context->language->id . '_index_sitemap.xml'));

		if ($this->cron) {
			die();
		}

		return true;

	}

	protected function _addSitemapNode($fd, $loc, $priority, $change_freq, $last_mod = null) {

		fwrite($fd, '<loc>' . ($this->context->phenyxConfig->get('EPH_REWRITING_SETTINGS') ? '<![CDATA[' . $loc . ']]>' : $loc) . '</loc>' . "\r\n" . '<priority>' . number_format($priority, 1, '.', '') . '</priority>' . "\r\n" . ($last_mod ? '<lastmod>' . date('c', strtotime($last_mod)) . '</lastmod>' : '') . "\r\n" . '<changefreq>' . $change_freq . '</changefreq>' . "\r\n");
	}

	protected function _addSitemapNodeImage($fd, $link, $title, $caption) {

		fwrite($fd, '<image:image>' . "\r\n" . '<image:loc>' . ($this->context->phenyxConfig->get('EPH_REWRITING_SETTINGS') ? '<![CDATA[' . $link . ']]>' : $link) . '</image:loc>' . "\r\n" . '<image:caption><![CDATA[' . $caption . ']]></image:caption>' . "\r\n" . '<image:title><![CDATA[' . $title . ']]></image:title>' . "\r\n" . '</image:image>' . "\r\n");
	}

	protected function _getPriorityPage($page) {

		return $this->context->phenyxConfig->get('GSITEMAP_PRIORITY_' . Tools::strtoupper($page)) ? $this->context->phenyxConfig->get('GSITEMAP_PRIORITY_' . Tools::strtoupper($page)) : 0.1;
	}

	protected function _createIndexSitemap() {

		$sitemaps = Db::getInstance()->executeS('SELECT `link` FROM `' . _DB_PREFIX_ . 'gsitemap` WHERE id_lang = ' . $this->context->language->id);

		if (!$sitemaps) {
			return false;
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>';
		$xml_feed = new SimpleXMLElement($xml);

		foreach ($sitemaps as $link) {
			$sitemap = $xml_feed->addChild('sitemap');
			$sitemap->addChild('loc', 'http' . ($this->context->phenyxConfig->get('EPH_SSL_ENABLED') && $this->context->phenyxConfig->get('EPH_SSL_ENABLED_EVERYWHERE') ? 's' : '') . '://' . Tools::getDomain(false, true) . __EPH_BASE_URI__ . $link['link']);
			$sitemap->addChild('lastmod', date('c'));
		}

		file_put_contents($this->normalizeDirectory(_EPH_ROOT_DIR_) . $this->context->language->id . '_index_sitemap.xml', $xml_feed->asXML());

		return true;
	}

	protected function _saveSitemapLink($sitemap) {

		if ($sitemap) {
			$map = new GsiteMap();
			$map->link = $sitemap;
			$map->id_lang = (int) $this->context->language->id;
			return $map->add();
		}

		return false;
	}

	public function _addLinkToSitemap(&$link_sitemap, $new_link, $lang, &$index, &$i, $id_obj) {

		if ($i <= 25000 && memory_get_usage() < 100000000) {
			$link_sitemap[] = $new_link;
			$i++;

			return true;
		} else {
			$this->_recursiveSitemapCreator($link_sitemap, $lang, $index);

			if ($index % 20 == 0 && !$this->cron) {
				$_POST['continue'] = 1;
				$_POST['type'] = $new_link['type'];
				$_POST['lang'] = $lang;
				$_POST['id'] = (int) ($id_obj);
				$_POST['id_lang'] = $this->context->language->id;
				$returns = [
					'gsitemap_number'       => (int) $index,
					'gsitemap_refresh_page' => 1,
				];

				foreach ($returns as $key => $value) {
					$this->smartyAssign[$key] = $value;
				}

				return false;
			} else

			if ($index % 20 == 0 && $this->cron) {
				$_POST['continue'] = 1;
				$_POST['type'] = $new_link['type'];
				$_POST['lang'] = $lang;
				$_POST['id'] = (int) ($id_obj);
				$_POST['id_lang'] = $this->context->language->id;
				$this->createSitemap();

			} else {

				$_POST['continue'] = 1;
				$_POST['type'] = $new_link['type'];
				$_POST['lang'] = $lang;
				$_POST['id'] = (int) ($id_obj);
				$_POST['id_lang'] = $this->context->language->id;
				$this->createSitemap();
			}

		}

	}

	protected function _getHomeLink(&$link_sitemap, $lang, &$index, &$i) {

		if ($this->context->phenyxConfig->get('EPH_SSL_ENABLED') && $this->context->phenyxConfig->get('EPH_SSL_ENABLED_EVERYWHERE')) {
			$protocol = 'https://';
		} else {
			$protocol = 'http://';
		}

		return $this->_addLinkToSitemap(
			$link_sitemap, [
				'type'  => 'home',
				'page'  => 'home',
				'link'  => $protocol . Tools::getDomainSsl(false) . $this->context->company->getBaseURI() . (method_exists('Language', 'isMultiLanguageActivated') ? Language::isMultiLanguageActivated() ? $lang['iso_code'] . '/' : '' : ''),
				'image' => false,
			], $lang['iso_code'], $index, $i, -1
		);
	}

	protected function _getMetaLink(&$link_sitemap, $lang, &$index, &$i, $id_meta = 0) {

		$metas = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('*')
				->from('meta')
				->where('`controller` LIKE "front"')
				->where('`configurable` > 0')
				->where('`id_meta` >= ' . (int) $id_meta)
				->orderBy('id_meta ASC')
		);

		foreach ($metas as $meta) {
			$url = '';

			if (!in_array($meta['page'], $this->disable_link)) {
				$url = $this->context->_link->getPageLink($meta['page'], null, $lang['id_lang']);

				if (!$this->_addLinkToSitemap(
					$link_sitemap, [
						'type'  => 'meta',
						'page'  => $meta['page'],
						'link'  => $url,
						'image' => false,
					], $lang['iso_code'], $index, $i, $meta['id_meta']
				)) {
					return false;
				}

			}

		}

		return true;
	}

	protected function _getCmsLink(&$link_sitemap, $lang, &$index, &$i, $id_cms = 0) {

		$cmss_id = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('c.`id_cms`')
				->from('cms', 'c')
				->leftJoin('cms_lang', 'cl', 'c.`id_cms` = cl.`id_cms` AND cl.`id_lang` = ' . (int) $lang['id_lang'])
				->where('c.`active` = 1 AND c.`indexation` = 1 AND c.`id_cms` >= 1')
				->where('c.`id_cms` >= ' . (int) $id_cms)
				->orderBy('c.`id_cms` ASC')
		);

		if (is_array($cmss_id)) {

			foreach ($cmss_id as $cms_id) {
				$cms = new CMS((int) $cms_id['id_cms'], $lang['id_lang']);
                $image_cms = null;
				$cms->link_rewrite = urlencode((is_array($cms->link_rewrite) ? $cms->link_rewrite[(int) $lang['id_lang']] : $cms->link_rewrite));
				$url = $this->context->_link->getCMSLink($cms, null, null, $lang['id_lang']);
                if (isset($cms->image_hash)) {
                    $image_cms = [
                        'title_img' => htmlspecialchars(strip_tags($cms->meta_title)),
                        'caption'   => htmlspecialchars(strip_tags(Composer::removeShortCode($cms->content))),
                        'link'      => $cms->image_hash,
                    ];
                }

				if (!$this->_addLinkToSitemap(
					$link_sitemap, [
						'type'  => 'cms',
						'page'  => 'cms',
						'link'  => $url,
						'image' => $image_cms,
					], $lang['iso_code'], $index, $i, $cms_id['id_cms']
				)) {
					return false;
				}

			}

		}

		return true;
	}

	protected function _getPluginLink(&$link_sitemap, $lang, &$index, &$i, $num_link = 0) {

		$plugins_links = $this->context->_hook->exec(self::HOOK_ADD_URLS, ['lang' => $lang], null, true);

		if (empty($plugins_links) || !is_array($plugins_links)) {
			return true;
		}

		$links = [];

		foreach ($plugins_links as $plugin_links) {
			$links = array_merge($links, $plugin_links);
		}

		foreach ($plugin_links as $n => $link) {

			if ($num_link > $n) {
				continue;
			}

			if (!$this->_addLinkToSitemap($link_sitemap, $link, $lang['iso_code'], $index, $i, $n)) {
				return false;
			}

		}

		return true;
	}

	public function generateRobotsFile() {

		if (!$writeFd = @fopen($this->rb_file, 'w')) {
			return sprintf($this->l('Cannot write into file: %s. Please check write permissions.'), $this->rb_file);
			$return = [
				'success' => false,
				'message' => sprintf($this->l('Cannot write into file: %s. Please check write permissions.'), $this->rb_file),
			];
			die(Tools::jsonEncode($return));
		} else {

			$this->context->_hook->exec(
				'actionAdminMetaBeforeWriteRobotsFile',
				[
					'rb_data' => &$this->rb_data,
				]
			);

			// PS Comments
			fwrite($writeFd, "# robots.txt automatically generated by ephenyx e-commerce open-source solution\n");
			fwrite($writeFd, "# http://www.ephenyx.com - http://www.ephenyx.com/forums\n");
			fwrite($writeFd, "# This file is to prevent the crawling and indexing of certain parts\n");
			fwrite($writeFd, "# of your site by web crawlers and spiders run by sites like Yahoo!\n");
			fwrite($writeFd, "# and Google. By telling these \"robots\" where not to go on your site,\n");
			fwrite($writeFd, "# you save bandwidth and server resources.\n");
			fwrite($writeFd, "# For more information about the robots.txt standard, see:\n");
			fwrite($writeFd, "# http://www.robotstxt.org/robotstxt.html\n");

			// User-Agent
			fwrite($writeFd, "User-agent: *\n");

			// Allow Directives

			if (count($this->rb_data['Allow'])) {
				fwrite($writeFd, "# Allow Directives\n");

				foreach ($this->rb_data['Allow'] as $allow) {
					fwrite($writeFd, 'Allow: ' . $allow . "\n");
				}

			}

			// Private pages

			// Directories

			if (count($this->rb_data['Directories'])) {
				fwrite($writeFd, "# Directories\n");

				foreach ($this->rb_data['Directories'] as $dir) {
					fwrite($writeFd, 'Disallow: */' . $dir . "\n");
				}

			}

			// Files

			if (count($this->rb_data['Files'])) {
				$activeLanguageCount = count(Language::getIDs());
				fwrite($writeFd, "# Files\n");

				foreach ($this->rb_data['Files'] as $isoCode => $files) {

					foreach ($files as $file) {

						if ($activeLanguageCount > 1) {
							// Friendly URLs have language ISO code when multiple languages are active
							fwrite($writeFd, 'Disallow: /' . $isoCode . '/' . $file . "\n");
						} else

						if ($activeLanguageCount == 1) {
							// Friendly URL does not have language ISO when only one language is active
							fwrite($writeFd, 'Disallow: /' . $file . "\n");
						} else {
							fwrite($writeFd, 'Disallow: /' . $file . "\n");
						}

					}

				}

			}

			// Sitemap

			if (file_exists($this->sm_file) && filesize($this->sm_file)) {
				fwrite($writeFd, "# Sitemap\n");
				$sitemapFilename = basename($this->sm_file);
				fwrite($writeFd, 'Sitemap: ' . ($this->context->phenyxConfig->get('EPH_SSL_ENABLED') ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . __EPH_BASE_URI__ . $sitemapFilename . "\n");
			}

			$this->context->_hook->exec(
				'actionAdminMetaAfterWriteRobotsFile',
				[
					'rb_data'  => $this->rb_data,
					'write_fd' => &$writeFd,
				]
			);

			fclose($writeFd);

			return true;

		}

	}

	public function getRobotsContent() {

		$tab = [];

		// Special allow directives
		$tab['Allow'] = ['*/plugins/*.css', '*/plugins/*.js'];

		// Directories
		$tab['Directories'] = ['includes/classes/', 'app/', 'content/download/', 'content/mails/', 'includes/plugins/', 'content/translations/'];

		// Files
		$disallowControllers = [
			'footer', 'get-file', 'identity', 'my-account', 'password', 'statistics', 'guest-tracking',
		];
		$disallows = $this->context->_hook->exec('actionDisallowControllers', [], null, true);

		if (is_array($disallows)) {

			foreach ($disallows as $plugin => $values) {

				if (is_array($values)) {

					foreach ($values as $value) {
						$disallowControllers[] = $value;
					}

				} else

				if (!empty($values) && is_string($values)) {
					$disallowControllers[] = $values;
				}

			}

		}

		// Rewrite files
		$tab['Files'] = [];

		if ($this->context->phenyxConfig->get('EPH_REWRITING_SETTINGS')) {
			$sql = new DbQuery();
			$sql->select('ml.url_rewrite, l.iso_code');
			$sql->from('meta', 'm');
			$sql->innerJoin('meta_lang', 'ml', 'ml.id_meta = m.id_meta');
			$sql->innerJoin('lang', 'l', 'l.id_lang = ml.id_lang');
			$sql->where('l.active = 1 AND m.controller = "front" AND m.page IN (\'' . implode('\', \'', $disallowControllers) . '\')');

			if ($results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql)) {

				foreach ($results as $row) {
					$tab['Files'][$row['iso_code']][] = $row['url_rewrite'];
				}

			}

		}

		return $tab;
	}

}
