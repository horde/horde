<?php
/**
 * Selectlist handler.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Gollem
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('gollem', array(
    'authentication' => 'selectlist'
));

/* Set directory. */
try {
    Gollem::changeDir();
} catch (Gollem_Exception $e) {
    $notification->push($e);
}
$currdir = Gollem::getDir();

/* Create a new cache ID if one does not already exist. */
$cacheid = Horde_Util::getFormData('cacheid');
if (empty($cacheid)) {
    $cacheid = strval(new Horde_Support_Randomid());
}
$selectlist = $session->get('gollem', 'selectlist/' . $cacheid, Horde_Session::TYPE_ARRAY);

/* Get the formid for the return. */
$formid = Horde_Util::getFormData('formid');

/* Run through the action handlers. */
switch (Horde_Util::getFormData('actionID')) {
case 'select':
    $items = Horde_Util::getPost('items');
    if (is_array($items) && count($items)) {
        foreach ($items as $item) {
            $item_value = $currdir . '|' . $item;
            if (empty($selectlist['files'])) {
                $selectlist['files'] = array($item_value);
            } else {
                $item_key = array_search($item_value, $selectlist['files']);
                if ($item_key !== false) {
                    unset($selectlist['files'][$item_key]);
                    sort($selectlist['files']);
                } else {
                    $selectlist['files'][] = $item_value;
                }
            }
        }

        $session->set('gollem', 'selectlist/' . $cacheid, $selectlist);

        $filelist = array_keys(array_flip($selectlist['files']));
    }
    break;
}

try {
    $info = array(
        'list' => Gollem::listFolder($currdir)
    );
} catch (Gollem_Exception $e) {
    /* If that didn't work, fall back to the parent or the home directory. */
    $notification->push(sprintf(_("Permission denied to %s: %s"), $currdir, $e->getMessage()), 'horde.error');

    $loc = strrpos($currdir, '/');
    Gollem::setDir(($loc !== false) ? substr($currdir, 0, $loc) : Gollem::getHome());
    $currdir = Gollem::getDir();
    $info = array(
        'list' => Gollem::listFolder($currdir)
    );
}

$info['title'] = htmlspecialchars($GLOBALS['gollem_be']['label']);

/* Image links. */
$folder_img = Horde::img('folder.png', _("folder"));
$symlink_img = Horde::img('folder_symlink.png', _("symlink"));

/* Commonly used URLs. */
$self_url = Horde::url('selectlist.php');

/* Set up the template object. */
$t = $injector->createInstance('Horde_Template');
$t->set('addbutton', _("Add"));
$t->set('donebutton', _("Done"));
$t->set('cancelbutton', _("Cancel"));
$t->set('self_url', $self_url);
$t->set('forminput', Horde_Util::formInput());
$t->set('cacheid', htmlspecialchars($cacheid));
$t->set('currdir', htmlspecialchars($currdir));
$t->set('formid', htmlspecialchars($formid));
$t->set('navlink', Gollem::directoryNavLink($currdir, $self_url->copy()->add(array('cacheid' => $cacheid, 'formid' => $formid))));
if ($GLOBALS['conf']['backend']['backend_list'] == 'shown') {
    // TODO
    //$t->set('changeserver', Horde::link(htmlspecialchars(Horde_Auth::addLogoutParameters(Horde_Util::addParameter(Horde::url('login.php'), array('url' => Horde_Util::addParameter(Horde::url('selectlist.php'), array('formid' => $formid)))), Horde_Auth::REASON_LOGOUT)), _("Change Server")) . Horde::img('logout.png', _("Change Server")) . '</a>', true);
} else {
    $t->set('changeserver', '', true);
}

if (is_array($info['list']) &&
    count($info['list']) &&
    Gollem::checkPermissions('backend', Horde_Perms::READ)) {

    $entry = $icon_cache = array();
    $rowct = 0;

    foreach ($info['list'] as $key => $val) {
        $item = array(
          'dir' => false,
          'name' => htmlspecialchars($val['name']),
          'selected' => false,
          'type' => $val['type']
        );

        $name = str_replace(' ', '&nbsp;', $item['name']);

        /* Determine graphic to use. */
        if (!empty($val['link'])) {
            $item['graphic'] = $symlink_img;
        } elseif ($val['type'] == '**dir') {
            $item['graphic'] = $folder_img;
        } else {
            if (empty($icon_cache[$val['type']])) {
                $icon_cache[$val['type']] = $injector->getInstance('Horde_Core_Factory_MimeViewer')->getIcon($val['type']);
            }
            $item['graphic'] = $icon_cache[$val['type']];
        }

        /* Create proper link. */
        switch ($val['type']) {
        case '**dir':
            $url = $self_url->copy()->add(array(
                'cacheid' => $cacheid,
                'dir' => Gollem::subdirectory($currdir, $val['name']),
                'formid' => $formid
            ));
            $item['link'] = $url->link() . '<strong>' . $name . '</strong></a>';
            $item['dir'] = true;
            break;

        case '**sym':
            if ($val['linktype'] === '**dir') {
                if (substr($val['link'], 0, 1) == '/') {
                    $parts = explode('/', $val['link']);
                    $name = array_pop($parts);
                    $dir = implode('/', $parts);
                } else {
                    $name = $val['link'];
                    $dir = $currdir;
                }

                $url = $self_url->copy()->add(array(
                    'cacheid' => $cacheid,
                    'dir' => Gollem::subdirectory($currdir, $val['name']),
                    'formid' => $formid
                ));
                $item['link'] = $item['name'] . ' -> <strong>' . $url->link() . $val['link'] . '</a></strong>';
            } else {
                $item['link'] = $item['name'] . ' -> ' . $val['link'];
            }
            break;

        default:
            $item['link'] = $name;
            break;
        }

        if (!empty($selectlist['files']) &&
            in_array($currdir . '|' . $val['name'], $selectlist['files'])) {
            $item['selected'] = true;
        }

        $item['item'] = (++$rowct % 2) ? 'item0' : 'item1';

        $entry[] = $item;
    }

    $t->set('entry', $entry, true);
    $t->set('nofiles', '', true);
} else {
    $t->set('nofiles', _("There are no files in this folder."), true);
}

$title = $info['title'];
Horde::addScriptFile('selectlist.js', 'gollem');
Horde::addInlineJsVars(array(
    'cacheid' => $cacheid,
    'formid' => $formid
));
require $registry->get('templates', 'horde') . '/common-header.inc';
require GOLLEM_TEMPLATES . '/javascript_defs.php';
Gollem::status();
echo $t->fetch(GOLLEM_TEMPLATES . '/selectlist/selectlist.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
