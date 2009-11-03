<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * not receive such a file, see also http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/base.php';

/* Load traditional interface? */
if (!$prefs->getValue('dynamic_view') || !$browser->hasFeature('xmlhttpreq')) {
    include KRONOLITH_BASE . '/' . $prefs->getValue('defaultview') . '.php';
    exit;
}

/* Load Ajax interface. */
$logout_link = Horde::getServiceLink('logout', 'kronolith');
if ($logout_link) {
    $logout_link = Horde::widget($logout_link, _("_Logout"), 'logout');
}
$help_link = Horde::getServiceLink('help', 'kronolith');
if ($help_link) {
    $help_link = Horde::widget($help_link, _("Help"), 'helplink', 'help', Horde::popupJs($help_link, array('urlencode' => true)) . 'return false;');
}
$today = Kronolith::currentDate();
$_SESSION['horde_prefs']['nomenu'] = true;

$datejs = str_replace('_', '-', $language) . '.js';
if (!file_exists($registry->get('jsfs') . '/' . $datejs)) {
    $datejs = 'en-US.js';
}
$scripts = array(
    array($datejs, 'kronolith', true),
    array('date.js', 'kronolith', true),
    array('dhtmlHistory.js', 'horde', true),
    array('redbox.js', 'horde', true),
    array('tooltips.js', 'horde', true),
);
Kronolith::header('', $scripts);
echo "<body class=\"kronolithAjax\">\n";
require KRONOLITH_TEMPLATES . '/index/index.inc';
Horde::includeScriptFiles();
Horde::outputInlineScript();
$notification->notify(array('listeners' => array('javascript')));
$tac = Horde_Ajax_Imple::factory(array('kronolith', 'TagAutoCompleter'), array('triggerId' => 'kronolithEventTags', 'pretty' => true));
$tac->attach();
echo "</body>\n</html>";
