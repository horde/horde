<?php
/**
 * Browse
 *
 * Copyright 2007 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: browse.php 210 2008-01-10 12:41:43Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */
define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';
require_once NEWS_BASE . '/lib/Forms/Search.php';

// Default vars
$title = _("Browse");
$page = Util::getGet('news_page', 0);
$per_page = $prefs->getValue('per_page');
$news_url = Horde::applicationUrl('news.php');
$browse_url = Horde::applicationUrl('browse.php');
$cid = Util::getGet('cid');

// Define creteria
if (!empty($_GET)) {
    $criteria = $_GET;
    $browse_url = Util::addParameter($browse_url, $_GET);
} else {
    $criteria = array();
}

// Count rows
$count = $news->countNews($criteria);
if ($count instanceof PEAR_Error) {
    echo $count->getMessage() . ': ' . $count->getDebugInfo();
    exit;
}


$rows = $news->listNews($criteria, $page*$per_page, $per_page);
if ($rows instanceof PEAR_Error) {
    echo $rows->getMessage() . ': ' . $rows->getDebugInfo();
    exit;
}

// If we have only one row redirect ot it
if ($count == 1 && sizeof($cats) < 2 && $page < 1) {
    header('Location: '. Util::addParameter($news_url, 'id', $rows[0]['id'], false));
    exit;
}

// Get pager
$pager = News_Search::getPager(array(), $count, $browse_url);

require_once NEWS_TEMPLATES . '/common-header.inc';
require_once NEWS_TEMPLATES . '/menu.inc';

$browse_template_path = News::getTemplatePath($cid, 'browse');
require_once $browse_template_path . 'header.inc';
foreach ($rows as $row) {
    require $browse_template_path . 'row.inc';
}
require_once $browse_template_path . '/footer.inc';

require_once $registry->get('templates', 'horde') . '/common-footer.inc';
