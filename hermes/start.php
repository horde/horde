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

require_once dirname(__FILE__) . '/lib/Application.php';

$hermes = Horde_Registry::appInit('hermes');
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, _("Stop Watch"));
$form->addVariable(_("Stop watch description"), 'description', 'text', true);

if ($form->validate($vars)) {
    Hermes::newTimer($vars->get('description'));
    echo Horde::wrapInlineScript(array(
        'var t = ' . Horde_Serialize::serialize(sprintf(_("The stop watch \"%s\" has been started and will appear in the sidebar at the next refresh."), $vars->get('description')), Horde_Serialize::JSON) . ';',
        'alert(t);',
        'window.close();'
    ));
    exit;
}

$title = _("Stop Watch");
require $registry->get('templates', 'horde') . '/common-header.inc';

$renderer = new Horde_Form_Renderer();
$form->renderActive($renderer, $vars, Horde::url('start.php'), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
