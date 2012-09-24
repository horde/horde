<?php
/**
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';

$hermes = Horde_Registry::appInit('hermes');
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, _("Stop Watch"));
$form->addVariable(_("Stop watch description"), 'description', 'text', true);

if ($form->validate($vars)) {
    Hermes::newTimer($vars->get('description'));
    echo Horde::wrapInlineScript(array(
        'var t = ' . Horde_Serialize::serialize(sprintf(_("The stop watch \"%s\" has been started and will appear in the menu at the next refresh."), $vars->get('description')), Horde_Serialize::JSON) . ';',
        'alert(t);',
        'window.close();'
    ));
    exit;
}

$page_output->topbar = $page_output->sidebar = false;

$page_output->header(array(
    'title' => _("Stop Watch")
));
$form->renderActive(new Horde_Form_Renderer(), $vars, Horde::url('start.php'), 'post');
$page_output->footer();
