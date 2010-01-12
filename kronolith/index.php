<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * not receive such a file, see also http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/base.php';

/* Load traditional interface? */
if (!$prefs->getValue('dynamic_view') || !$browser->hasFeature('xmlhttpreq') ||
    ($browser->isBrowser('msie') && $browser->getMajor() <= 6)) {
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
$today = new Horde_Date($_SERVER['REQUEST_TIME']);
$_SESSION['horde_prefs']['nomenu'] = true;

Kronolith::header();
echo "<body class=\"kronolithAjax\">\n";
require KRONOLITH_TEMPLATES . '/index/index.inc';
Horde::includeScriptFiles();
Horde::outputInlineScript();
if ($conf['maps']['driver']) {
    Kronolith::initEventMap($conf['maps']);
}
$notification->notify(array('listeners' => array('javascript')));
$tac = Horde_Ajax_Imple::factory(array('kronolith', 'TagAutoCompleter'), array('triggerId' => 'kronolithEventTags', 'box' => 'kronolithEventACBox', 'pretty' => true));
$tac->attach();
echo "</body>\n</html>";
