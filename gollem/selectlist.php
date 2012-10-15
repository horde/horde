<?php
/**
 * Selectlist handler.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('gollem', array(
    'authentication' => 'selectlist'
));

$vars = Horde_Variables::getDefaultVariables();

/* Set directory. */
try {
    Gollem::changeDir();
} catch (Gollem_Exception $e) {
    $notification->push($e);
}

/* Create a new cache ID if one does not already exist. */
$cacheid = $vars->get('cacheid', strval(new Horde_Support_Randomid()));

$selectlist = $session->get('gollem', 'selectlist/' . $cacheid, Horde_Session::TYPE_ARRAY);

/* Run through the action handlers. */
switch ($vars->actionID) {
case 'select':
    if (is_array($vars->items) && count($vars->items)) {
        foreach ($vars->items as $item) {
            $item_value = Gollem::$backend['dir'] . '|' . $item;
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
    $info = array('list' => Gollem::listFolder(Gollem::$backend['dir']));
} catch (Gollem_Exception $e) {
    /* If that didn't work, fall back to the parent or the home directory. */
    $notification->push(sprintf(_("Permission denied to %s: %s"), Gollem::$backend['dir'], $e->getMessage()), 'horde.error');

    $loc = strrpos(Gollem::$backend['dir'], '/');
    Gollem::setDir(($loc !== false) ? substr(Gollem::$backend['dir'], 0, $loc) : Gollem::$backend['home']);
    $info = array('list' => Gollem::listFolder(Gollem::$backend['dir']));
}

$info['title'] = htmlspecialchars(Gollem::$backend['label']);

/* Commonly used URLs. */
$self_url = Horde::url('selectlist.php');

/* Set up the template object. */
$t = $injector->createInstance('Horde_Template');
$t->set('addbutton', _("Add"));
$t->set('donebutton', _("Done"));
$t->set('cancelbutton', _("Cancel"));
$t->set('self_url', $self_url);
$t->set('forminput', Horde_Util::formInput());
$t->set('cacheid', $cacheid);
$t->set('currdir', htmlspecialchars(Gollem::$backend['dir']));
$t->set('formid', htmlspecialchars($vars->formid));
$t->set('navlink', Gollem::directoryNavLink(Gollem::$backend['dir'], $self_url->copy()->add(array('cacheid' => $cacheid, 'formid' => $vars->formid))));
if ($GLOBALS['conf']['backend']['backend_list'] == 'shown') {
    // TODO
    //$t->set('changeserver', Horde::link(htmlspecialchars(Horde_Auth::addLogoutParameters(Horde::url('login.php')->add(array('url' => Horde::url('selectlist.php')->add(array('formid' => $vars->formid)))), Horde_Auth::REASON_LOGOUT)), _("Change Server")) . Horde::img('logout.png', _("Change Server")) . '</a>', true);
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
            $item['graphic'] = '<span class="iconImg symlinkImg"></span>';
        } elseif ($val['type'] == '**dir') {
            $item['graphic'] = '<span class="iconImg folderImg"></span>';
        } else {
            if (empty($icon_cache[$val['type']])) {
                $icon_cache[$val['type']] = Horde::img($injector->getInstance('Horde_Core_Factory_MimeViewer')->getIcon($val['type']));
            }
            $item['graphic'] = $icon_cache[$val['type']];
        }

        /* Create proper link. */
        switch ($val['type']) {
        case '**dir':
            $url = $self_url->copy()->add(array(
                'cacheid' => $cacheid,
                'dir' => Gollem::subdirectory(Gollem::$backend['dir'], $val['name']),
                'formid' => $vars->formid
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
                    $dir = Gollem::$backend['dir'];
                }

                $url = $self_url->copy()->add(array(
                    'cacheid' => $cacheid,
                    'dir' => Gollem::subdirectory(Gollem::$backend['dir'], $val['name']),
                    'formid' => $vars->formid
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
            in_array(Gollem::$backend['dir'] . '|' . $val['name'], $selectlist['files'])) {
            $item['selected'] = true;
        }

        $item['item'] = (++$rowct % 2) ? 'rowEven' : 'rowOdd';

        $entry[] = $item;
    }

    $t->set('entry', $entry, true);
    $t->set('nofiles', '', true);
} else {
    $t->set('nofiles', _("There are no files in this folder."), true);
}

$page_output->addScriptFile('selectlist.js');
$page_output->header(array(
    'title' => $info['title']
));
require GOLLEM_TEMPLATES . '/javascript_defs.php';
Gollem::status();
echo $t->fetch(GOLLEM_TEMPLATES . '/selectlist/selectlist.html');
$page_output->footer();
