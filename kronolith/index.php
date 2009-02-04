<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * not receive such a file, see also http://www.fsf.org/copyleft/gpl.html.
 */

@define('KRONOLITH_BASE', dirname(__FILE__));
$kronolith_configured = (is_readable(KRONOLITH_BASE . '/config/conf.php') &&
                         is_readable(KRONOLITH_BASE . '/config/prefs.php'));

if (!$kronolith_configured) {
    require KRONOLITH_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Kronolith', KRONOLITH_BASE,
        array('conf.php', 'prefs.php'));
}

require_once KRONOLITH_BASE . '/lib/base.php';

/* Load traditional interface? */
if (!$prefs->getValue('dynamic_view') || !$browser->hasFeature('xmlhttpreq')) {
    include KRONOLITH_BASE . '/' . $prefs->getValue('defaultview') . '.php';
    exit;
}

/* Load Ajax interface. */
require_once 'Horde/Identity.php';
$identity = Identity::factory();
$logout_link = Horde::getServiceLink('logout', 'kronolith');
if ($logout_link) {
    $logout_link = Horde::widget($logout_link, _("_Logout"), 'logout');
}
$help_link = Horde::getServiceLink('help', 'kronolith');
if ($help_link) {
    $help_link = Horde::widget($help_link, _("Help"), 'helplink', 'help', 'popup(this.href); return false;');
}
$today = Kronolith::currentDate();
$remote_calendars = @unserialize($prefs->getValue('remote_cals'));
$current_user = Auth::getAuth();
$my_calendars = array();
$shared_calendars = array();
foreach (Kronolith::listCalendars() as $id => $cal) {
    if ($cal->get('owner') == $current_user) {
        $my_calendars[$id] = $cal;
    } else {
        $shared_calendars[$id] = $cal;
    }
}

$datejs = str_replace('_', '-', $language) . '.js';
if (!file_exists($registry->get('fileroot') . '/js/' . $datejs)) {
    $datejs = 'en-US.js';
}
$scripts = array(
    array($datejs, 'kronolith', true),
    array('date.js', 'kronolith', true),
    array('ContextSensitive.js', 'kronolith', true),
    array('dhtmlHistory.js', 'horde', true),
    array('redbox.js', 'horde', true),
);
Kronolith::header('', $scripts);
echo "<body class=\"kronolithAjax\">\n";
require KRONOLITH_TEMPLATES . '/index/index.inc';
Kronolith::includeScriptFiles();
Kronolith::outputInlineScript();
$notification->notify(array('listeners' => array('javascript')));
echo "</body>\n</html>";
