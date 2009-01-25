<?php
/**
 * Print news
 *
 * $Id: print.php 803 2008-08-27 08:29:20Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */
require_once dirname(__FILE__) . '/lib/base.php';

$id = Util::getFormData('id');
$version = Util::getFormData('version', false);
$row = $news->get($id);
$template_path = News::getTemplatePath($row['category1'], 'news');

require_once NEWS_TEMPLATES . '/print/header.inc';
require $template_path . 'news.inc';
require_once NEWS_TEMPLATES . '/print/footer.inc';
