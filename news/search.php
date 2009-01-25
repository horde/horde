<?php
/**
 * Search
 *
 * $Id: search.php 889 2008-09-23 09:52:06Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */
require_once dirname(__FILE__) . '/lib/base.php';
require_once NEWS_BASE . '/lib/Forms/Search.php';

// Default vars
$title = _("Browse");
$page = Util::getGet('news_page', 0);
$per_page = $prefs->getValue('per_page');
$browse_url = Horde::applicationUrl('browse.php');
$cid = Util::getGet('cid');

$vars = Variables::getDefaultVariables();
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

$vars = Variables::getDefaultVariables();
$pager = new Horde_UI_Pager('news_page',
                            $vars, array('num' => $count,
                                         'url' => 'search.php',
                                         'perpage' => $per_page));

$pager->preserve($criteria);

// If we have only one row redirect ot it
if ($count == 1 && sizeof($cats) < 2 && $page < 1) {
    header('Location: '. News::getUrlFor('news', $rows[0]['id']));
    exit;
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