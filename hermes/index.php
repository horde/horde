<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('hermes');

/* Traditional? */
$mode = $session->get('horde', 'mode');
if (!Hermes::showAjaxView()) {
    if ($mode == 'dynamic' || ($mode == 'auto' && $prefs->getValue('dynamic_view'))) {
        $notification->push(_("Your browser is too old to display the dynamic mode. Using traditional mode instead."), 'horde.warning');
        $session->set('horde', 'mode', 'traditional');
    }
    include HERMES_BASE . '/time.php';
    exit;
}

$help_link = Horde::getServiceLink('help', 'hermes');
if ($help_link) {
    $help_link = Horde::widget($help_link, _("Help"), 'helplink', 'help', Horde::popupJs($help_link, array('urlencode' => true)) . 'return false;');
}

$today = new Horde_Date();
Hermes::header();
echo "<body class=\"hermesAjax\">\n";
$menu = new Horde_Menu();
require HERMES_TEMPLATES . '/index/index.inc';
Horde::includeScriptFiles();
Horde::outputInlineScript();
echo "</body>\n</html>";
