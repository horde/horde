<?php
/**
 * Browse
 *
 * $Id: browse.php 1179 2009-01-20 13:19:34Z duck $
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

// Default vars
$title = _("Browse");
$page = Horde_Util::getGet('news_page', 0);
$per_page = $prefs->getValue('per_page');
$browse_url = Horde::url('browse.php');
$cid = Horde_Util::getGet('cid');

// Define creteria
if (!empty($_GET)) {
    $criteria = $_GET;
    $browse_url = Horde_Util::addParameter($browse_url, $_GET);
} else {
    $criteria = array();
}

// Count rows
$count = $news->countNews($criteria);
if ($count instanceof PEAR_Error) {
    echo $count->getMessage() . ': ' . $count->getDebugInfo();
    exit;
}

// Get news
$rows = $news->listNews($criteria, $page*$per_page, $per_page);
if ($rows instanceof PEAR_Error) {
    echo $rows->getMessage() . ': ' . $rows->getDebugInfo();
    exit;
}

// If we have only one row redirect ot it
if ($count == 1 && sizeof($cats) < 2 && $page < 1) {
    News::getUrlFor('news', $rows[0]['id'])->redirect();
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
