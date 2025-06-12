<?php

$vc = ephenyx_manager();
extract(Composer::shortcode_atts([
	'id'             => '',
	'attach_pdf'     => '',
	'btnDownloadPdf' => false,
	'btnPrint'       => false,
	'btnShare'       => false,
], $atts));
$context = Context::getContext();
$context->controller->addJS('https://cdn.ephenyx.io/pdfWorker/flipbook.min.js');
$context->controller->addCSS(_EPH_CSS_DIR_ . 'flipbook.style.css');
$path = ComposerMedia::getPathMediaById($id);
$data = $context->smarty->createTemplate(_EPH_ALL_THEMES_DIR_ . 'script.tpl');
$data->assign([
	'pdf_dir'        => $path,
	'btnDownloadPdf' => isset($btnDownloadPdf) ? 1 : 0,
	'btnPrint'       => isset($btnPrint) ? 1 : 0,
	'btnShare'       => isset($btnShare) ? 1 : 0,
]);
$script = $data->fetch();
$context->smarty->assign('script', $script);
