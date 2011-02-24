<?php
/**
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('hermes');

$menu = new Horde_Menu();
$help_link = Horde::getServiceLink('help', 'hermes');
if ($help_link) {
    $help_link = Horde::widget($help_link, _("Help"), 'helplink', 'help', Horde::popupJs($help_link, array('urlencode' => true)) . 'return false;');
}

$clients = Hermes::listClients();
$today = new Horde_Date();
Hermes::header();
echo "<body class=\"hermesAjax\">\n";
require HERMES_TEMPLATES . '/index/index.inc';
Horde::includeScriptFiles();
Horde::outputInlineScript();
echo "</body>\n</html>";