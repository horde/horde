<?php
/**
 * Search
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
$browse_url = Horde::applicationUrl('browse.php');
$cid = Horde_Util::getGet('cid');

$vars = Horde_Variables::getDefaultVariables();
$form = new News_Search($vars);
$form->getInfo(null, $criteria);

// Count rows
$count = $news->countNews($criteria);
if ($count instanceof PEAR_Error) {
    echo $count->getMessage() . ': ' . $count->getDebugInfo();
    exit;
}

// Select rows
$rows = $news->listNews($criteria, $page*$per_page, $per_page, null);
if ($rows instanceof PEAR_Error) {
    echo $rows->getMessage() . ': ' . $rows->getDebugInfo();
    exit;
}

$pager = News_Search::getPager($criteria, $count, 'search.php');

$pager->preserve($criteria);

// If we have only one row redirect ot it
if ($count == 1 && sizeof($cats) < 2 && $page < 1) {
    News::getUrlFor('news', $rows[0]['id'])->redirect();
}


require_once NEWS_TEMPLATES . '/common-header.inc';
require_once NEWS_TEMPLATES . '/menu.inc';

$form->renderActive(null, null, null, 'post');

$browse_template_path = News::getTemplatePath($cid, 'browse');
require_once $browse_template_path . 'header.inc';
foreach ($rows as $row) {
    require $browse_template_path . 'row.inc';
}
require_once $browse_template_path . '/footer.inc';

require_once $registry->get('templates', 'horde') . '/common-footer.inc';
