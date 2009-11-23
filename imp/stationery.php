<?php
/**
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => array('authentication' => 'horde')));

$compose_url = Horde::getServiceLink('options', 'imp');

/* Is the preference locked? */
if ($prefs->isLocked('stationery')) {
    header('Location: ' . $compose_url);
    exit;
}

/* Retrieve stationery. */
$stationery_list = @unserialize($prefs->getValue('stationery', false));
$stationery_list = is_array($stationery_list)
    ? Horde_String::convertCharset($stationery_list, $prefs->getCharset())
    : array();

/* Get form data. */
$selected = Horde_Util::getFormData('stationery');
if (strlen($selected)) {
    $selected = (int)$selected;
}

/* Always check for stationery type switches. */
$content = Horde_Util::getFormData('content', '');
$last_type = Horde_Util::getFormData('last_type');
$name = Horde_Util::getFormData('name', '');
$type = Horde_Util::getFormData('type', 'plain');
if (!empty($last_type) && $last_type != $type) {
    $content = ($type == 'plain')
        ? Horde_Text_Filter::filter($content, 'html2text')
        : nl2br(htmlspecialchars(htmlspecialchars($content)));
}
$stationery = array('n' => $name, 't' => $type, 'c' => $content);

/* Run through the action handlers. */
$actionID = Horde_Util::getFormData('actionID');
$updated = false;
switch ($actionID) {
case 'update':
    if (Horde_Util::getFormData('edit')) {
        /* Stationery has been switched. */
        if (strlen($selected)) {
            /* Edit existing. */
            $stationery = array(
                'n' => $stationery_list[$selected]['n'],
                't' => $stationery_list[$selected]['t'],
                'c' => $stationery_list[$selected]['c']
            );
        } else {
            $stationery = array(
                'n' => '',
                't' => 'plain',
                'c' => ''
            );
        }
    } elseif (Horde_Util::getFormData('delete')) {
        /* Delete stationery. */
        if (isset($stationery_list[$selected])) {
            $updated = sprintf(_("The stationery \"%s\" has been deleted."), $stationery_list[$selected]['n']);
            unset($stationery_list[$selected]);
            $selected = null;
        }
        $stationery = array(
            'n' => '',
            't' => 'plain',
            'c' => ''
        );
    } elseif (Horde_Util::getFormData('save')) {
        /* Saving stationery. */
        if (!strlen($selected)) {
            $selected = count($stationery_list);
            $stationery_list[] = $stationery;
            $updated = sprintf(_("The stationery \"%s\" has been added."), $stationery['n']);
        } else {
            $stationery_list[$selected] = $stationery;
            $updated = sprintf(_("The stationery \"%s\" has been updated."), $stationery['n']);
        }
    }
    break;
}

if ($updated) {
    $prefs->setValue('stationery', serialize(Horde_String::convertCharset($stationery_list, Horde_Nls::getCharset(), $prefs->getCharset())), false);
    $notification->push($updated, 'horde.success');
}

if ($stationery['t'] == 'html') {
    $editor = Horde_Editor::singleton('Fckeditor', array('id' => 'content'));
}

/* Show the header. */
extract(Horde::loadConfiguration('prefs.php', array('prefGroups', '_prefs'), 'imp'));

$app = 'imp';
$chunk = Horde_Util::nonInputVar('chunk');
Horde_Prefs_Ui::generateHeader(null, $chunk);

$t = new Horde_Template();
$t->setOption('gettext', true);
$t->set('action', Horde::selfUrl());
$t->set('forminput', Horde_Util::formInput());
$t->set('navcell', Horde_Util::bufferOutput(array('Horde_Prefs_Ui', 'generateNavigationCell'), 'compose'));

$slist = array();
foreach ($stationery_list as $key => $choice) {
    $slist[] = array(
        'val' => $key,
        'selected' => ($selected === $key),
        'text' => $choice['n'] . ' ' . ($choice['t'] == 'html' ? _("(HTML)") : _("(Plain Text)"))
    );
}
$t->set('slist', $slist);
$t->set('selected', strlen($selected));
$t->set('last_type', $stationery['t']);
$t->set('name_label', Horde::label('name', _("Stationery name:")));
$t->set('name', $stationery['n']);
$t->set('type_label', Horde::label('name', _("Stationery type:")));
$t->set('plain', $stationery['t'] == 'plain');
$t->set('html', $stationery['t'] == 'html');
$t->set('content_label', Horde::label('content', _("Stationery:")));
$t->set('content', $stationery['c']);
$t->set('button_href', Horde_Util::addParameter($compose_url, array('group' => 'compose')));
$t->set('button_val', htmlspecialchars(_("Return to Message Composition"), ENT_COMPAT, Horde_Nls::getCharset()));

echo $t->fetch(IMP_TEMPLATES . '/stationery/stationery.html');
if (!$chunk) {
    require $registry->get('templates', 'horde') . '/common-footer.inc';
}
