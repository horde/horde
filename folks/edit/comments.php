<?php
/**
 * $Id: comments.php 974 2008-10-07 19:46:00Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

define('FOLKS_BASE', dirname(__FILE__) . '/..');
require_once FOLKS_BASE . '/lib/base.php';
require_once 'tabs.php';

$title = _("Comments");
$profile = $folks_driver->getProfile();
if ($profile instanceof PEAR_Error) {
    $notification->push($profile);
    Folks::getUrlFor('list', 'list')->redirect();
}

$comments = array(
    'never' => _("No one"),
    'all' => _("Any one"),
    'authenticated' => _("Authenticated users"),
    'moderate' => _("Moderate comments - I will approve every single comment")
);

if ($conf['comments']['allow'] == 'authenticated') {
    unset($comments['all']);
}

$form = new Horde_Form($vars, $title, 'comments');
$v = $form->addVariable(_("Who can post comments to your profile"), 'user_comments', 'radio', false, false, null, array($comments));
$v->setDefault('authenticated');
$form->setButtons(array(_("Save"), _("Delete all current comments")));

if (!$form->isSubmitted()) {
    $vars->set('user_comments', $profile['user_comments']);

} elseif ($form->validate()) {

    if (Horde_Util::getFormData('submitbutton') == _("Delete all current comments")) {

        try {
            $registry->call('forums/deleteForum', array('folks', $GLOBALS['registry']->getAuth()));
            $result = $folks_driver->updateComments($GLOBALS['registry']->getAuth(), true);
            if ($result instanceof PEAR_Error) {
                $notification->push($result);
            } else {
                $notification->push(_("Comments deleted successfuly"), 'horde.success');
            }
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
    } else {

        // Update forum status
        if ($vars->get('user_comments') == 'moderate' && $profile['user_comments'] != 'moderate' ||
            $vars->get('user_comments') != 'moderate' && $profile['user_comments'] == 'moderate') {

            $info = array('author' => $GLOBALS['registry']->getAuth(),
                            'forum_name' => $GLOBALS['registry']->getAuth(),
                            'forum_moderated' => ($profile['user_comments'] == 'moderate'));
            try {
                $registry->call('forums/saveFrom', array('folks', '', $info));
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }
        }

        // Update profile
        $result = $folks_driver->saveProfile(array('user_comments' => $vars->get('user_comments')));
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } else {
            $notification->push(_("Your comments preference was sucessfuly saved."), 'horde.success');
        }
    }
}

Horde::addScriptFile('tables.js', 'horde');
require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

echo $tabs->render('comments');
$form->renderActive(null, null, null, 'post');

if ($profile['user_comments'] == 'moderate') {
    echo '<br />';
    try {
        echo $registry->call('forums/moderateForm', array('folks'));
    } catch (Horde_Exception $e) {
        echo $e->getMessage();
    }
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
