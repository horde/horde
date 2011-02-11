<?php
 /**
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

define('FOLKS_BASE', dirname(__FILE__));
require_once FOLKS_BASE . '/lib/base.php';

if (Horde_Util::getFormData('submitbutton') == _("Close")) {

    echo '<script type="text/javascript">RedBox.close();</script>';

} elseif (Horde_Util::getFormData('formname') == 'savesearch') {

    $result = $folks_driver->saveSearch(Horde_Util::getFormData('search_criteria'), Horde_Util::getFormData('search_name'));
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    } else {
        $notification->push(_("Search criteria saved successfuly"), 'horde.success');
        Horde::url('search.php')->redirect();
    }

} elseif ((Horde_Util::getGet('delete') == 1) && Horde_Util::getGet('query')) {

    $result = $folks_driver->deleteSavedSearch(Horde_Util::getGet('query'));
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    } else {
        $notification->push(_("Search criteria deleted."), 'horde.success');
        Horde::url('search.php')->redirect();
    }
}

// Render
$vars = Horde_Variables::getDefaultVariables();
$vars->set('search_criteria', $session->get('folks', 'last_search'));
$form = new Horde_Form($vars, '', 'savesearch');
$form->addVariable(_("Name"), 'search_name', 'text', true);
$form->addHidden('', 'search_criteria', 'text', true);
$form->setButtons(array(_("Save"), _("Close")));
$notification->notify(array('listeners' => 'status'));
$form->renderActive(null, null, Horde::selfUrl(), 'post');
