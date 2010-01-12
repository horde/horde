<?php
/**
 * Print news
 *
 * $Id: print.php 803 2008-08-27 08:29:20Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */

require_once dirname(__FILE__) . '/lib/base.php';

$id = Horde_Util::getFormData('id');
$version = Horde_Util::getFormData('version', false);
$row = $news->get($id);
$template_path = News::getTemplatePath($row['category1'], 'news');

require_once NEWS_TEMPLATES . '/print/header.inc';
require $template_path . 'news.inc';
require_once NEWS_TEMPLATES . '/print/footer.inc';
