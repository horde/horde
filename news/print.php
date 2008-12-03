<?php
/**
 * Print news
 *
 * Copyright 2006 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: print.php 183 2008-01-06 17:39:50Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */

define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';

$id = Util::getFormData('id');
$version = Util::getFormData('version', false);
$row = $news->get($id);
$template_path = News::getTemplatePath($row['category1'], 'news');

require_once NEWS_TEMPLATES . '/print/header.inc';
require $template_path . 'news.inc';
require_once NEWS_TEMPLATES . '/print/footer.inc';
