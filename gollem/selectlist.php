<?php
/**
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => array('authentication' => 'selectlist'));

/* Set directory. */
if (is_a($result = Gollem::changeDir(), 'PEAR_Error')) {
    $notification->push($result);
}
$currdir = Gollem::getDir();

/* Create a new cache ID if one does not already exist. */
$cacheid = Horde_Util::getFormData('cacheid');
if (empty($cacheid)) {
    $cacheid = uniqid(mt_rand());
    $_SESSION['gollem']['selectlist'][$cacheid] = array();
}

/* Get the formid for the return. */
$formid = Horde_Util::getFormData('formid');

/* Run through the action handlers. */
$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'select':
    $items = Horde_Util::getPost('items');
    if (is_array($items) && count($items)) {
        foreach ($items as $item) {
            $item_value = $currdir . '|' . $item;
            if (empty($_SESSION['gollem']['selectlist'][$cacheid]['files'])) {
                $_SESSION['gollem']['selectlist'][$cacheid]['files'] = array($item_value);
            } else {
                $item_key = array_search($item_value, $_SESSION['gollem']['selectlist'][$cacheid]['files']);
                if ($item_key !== false) {
                    unset($_SESSION['gollem']['selectlist'][$cacheid]['files'][$item_key]);
                    sort($_SESSION['gollem']['selectlist'][$cacheid]['files']);
                } else {
                    $_SESSION['gollem']['selectlist'][$cacheid]['files'][] = $item_value;
                }
            }
        }
        $filelist = array_keys(array_flip($_SESSION['gollem']['selectlist'][$cacheid]['files']));
    }
    break;
}

$info = array();
$info['list'] = Gollem::listFolder($currdir);

/* If that didn't work, fall back to the parent or the home directory. */
if (is_a($info['list'], 'PEAR_Error')) {
    $notification->push(sprintf(_("Permission denied to %s: %s"), $currdir, $info['list']->getMessage()), 'horde.error');

    $loc = strrpos($currdir, '/');
    Gollem::setDir(($loc !== false) ? substr($currdir, 0, $loc) : Gollem::getHome());
    $currdir = Gollem::getDir();
    $info['list'] = Gollem::listFolder($currdir);
}

$info['title'] = htmlspecialchars($GLOBALS['gollem_be']['label']);

/* Image links. */
$folder_img = Horde::img('folder.png', _("folder"));
$symlink_img = Horde::img('folder_symlink.png', _("symlink"));

/* Commonly used URLs. */
$self_url = Horde::url('selectlist.php');

/* Now actually display everything, after we've notified the user of
   any errors. */
$backkey = $_SESSION['gollem']['backend_key'];
$list = $info['list'];
$title = $info['title'];

$js_code = array(
    'var cacheid = \'' . $cacheid . '\'',
    'var formid = \'' . $formid . '\'',
);

Horde::addScriptFile('selectlist.js', 'gollem');
require GOLLEM_TEMPLATES . '/common-header.inc';
Horde::addInlineScript(implode(';', $js_code));
Gollem::status();

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
$t->set('navlink', Gollem::directoryNavLink($currdir, Horde_Util::addParameter($self_url, array('cacheid' => $cacheid, 'formid' => $formid))));
if ($GLOBALS['conf']['backend']['backend_list'] == 'shown') {
    $t->set('changeserver', Horde::link(htmlspecialchars(Horde_Auth::addLogoutParameters(Horde_Util::addParameter(Horde::url('login.php'), array('url' => Horde_Util::addParameter(Horde::url('selectlist.php'), array('formid' => $formid)))), Horde_Auth::REASON_LOGOUT)), _("Change Server")) . Horde::img('logout.png', _("Change Server")) . '</a>', true);
} else {
    $t->set('changeserver', '', true);
}

if (is_array($list) &&
    count($list) &&
    Gollem::checkPermissions('backend', Horde_Perms::READ)) {

    $entry = $icon_cache = array();
    $rowct = 0;

    foreach ($list as $key => $val) {
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
                require_once 'Horde/MIME/Magic.php';
                require_once 'Horde/MIME/Viewer.php';
                if (is_callable(array('Horde', 'loadConfiguration'))) {
                    $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'horde');
                    extract($result);
                    $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'gollem');
                    $mime_drivers = array_merge_recursive($mime_drivers, $result['mime_drivers']);
                    $mime_drivers_map = array_merge_recursive($mime_drivers_map, $result['mime_drivers_map']);
                } else {
                    require HORDE_BASE . '/config/mime_drivers.php';
                    require GOLLEM_BASE . '/config/mime_drivers.php';
                }
                $icon_cache[$val['type']] = Horde::img(MIME_Viewer::getIcon(MIME_Magic::extToMIME($val['type'])), '', '', '');
            }
            $item['graphic'] = $icon_cache[$val['type']];
        }

        /* Create proper link. */
        switch ($val['type']) {
        case '**dir':
            $url = Horde_Util::addParameter($self_url, array('dir' => Gollem::subdirectory($currdir, $val['name']), 'cacheid' => $cacheid, 'formid' => $formid));
            $item['link'] = Horde::link($url) . '<strong>' . $name . '</strong></a>';
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

                $url = Horde_Util::addParameter($self_url, array('dir' => Gollem::subdirectory($currdir, $val['name']), 'cacheid' => $cacheid, 'formid' => $formid));
                $item['link'] = $item['name'] . ' -> <strong>' . Horde::link($url) . $val['link'] . '</a></strong>';
            } else {
                $item['link'] = $item['name'] . ' -> ' . $val['link'];
            }
            break;

        default:
            $item['link'] = $name;
            break;
        }

        if (!empty($_SESSION['gollem']['selectlist'][$cacheid]['files']) &&
            in_array($currdir . '|' . $val['name'], $_SESSION['gollem']['selectlist'][$cacheid]['files'])) {
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

echo $t->fetch(GOLLEM_TEMPLATES . '/selectlist/selectlist.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
