<?php
/**
 * Rdo backend tester
 *
 * Copyright 2006-2007 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Rdo
 */

@define('HORDE_BASE', dirname(__FILE__) . '/../../');
require_once HORDE_BASE . '/lib/base.php';

if (!Horde_Auth::isAdmin()) {
    die('Permission denied');
}

require_once 'Horde/Autoloader.php';
require_once dirname(__FILE__) . '/Form.php';
require_once dirname(__FILE__) . '/Table.php';

$what2process = Horde_Util::getFormData('what2process', 'Rdo');
$table = Horde_Util::getFormData('table', 'horde_user');
$self_url = Horde_Util::addParameter(Horde::selfUrl(false), array('table' => $table, 'what2process' => $what2process), null, false);

if ($what2process == 'Rdo') {

    /**
     */
    class Test extends Horde_Rdo_Base {
    }

    /**
     */
    class TestMapper extends Horde_Rdo_Mapper {

        /**
         * TestMapper constructor. Sets table name.
         */
        public function __construct($table)
        {
            $this->_table = $table;
        }

        public function getAdapter()
        {
            return Horde_Rdo_Adapter::factory('pdo', $GLOBALS['conf']['sql']);
        }

    }

    $mapper = new TestMapper($table);
} else {
    /**
     */
    $mapper = $table;
}

$action = Horde_Util::getFormData('action');

$filter = Horde_Util::getFormData('filter', array());
$title = sprintf('%s: %s', $action, $table);

$vars = Horde_Variables::getDefaultVariables();
$form = Horde_Form_Helper::factory($what2process, $vars, $title, null, $mapper);

switch ($action) {
case 'delete':
    $form->delete();
    $notification->push(_("Deleted"), 'horde.success');
    header('Location: ' . $self_url);
    exit;

case 'create':
    if (!$form->validate()) {
        break;
    }
    $form->getInfo($vars, $info);
    unset($info['action']);
    $form->create($info);
    $notification->push(_("Created"), 'horde.success');
    header('Location: ' . $self_url);
    exit;

case 'update':
    $test = $form->getSelected();
    if (!$form->isSubmitted()) {
        foreach ($test as $key => $value) {
            $vars->set($key, $value);
        }
    } elseif ($form->validate()) {
        $form->getInfo($vars, $info);
        unset($info['action']);
        $form->update($info);
        $notification->push(_("Updated"), 'horde.success');
        header('Location: ' . $self_url);
        exit;
    }
    break;

case 'search':
case 'search_active':
    $form->getInfo($vars, $filter);
    $self_url = Horde_Util::addParameter($self_url, 'action', 'search');
    break;
}

Horde::addScriptFile('stripe.js', 'horde', true);
require HORDE_TEMPLATES . '/common-header.inc';

echo "<h1>$what2process</h1>";

if (!in_array($action, array('create', 'update'))) {
    $template = Horde_Table_Helper::factory($what2process,
                                            array('filter' => $filter,
                                                  'url' => $self_url),
                                            $mapper);
    $template->fill();
    echo $template->fetch();
}

if (in_array($action, array('create', 'update', 'search', 'search_active'))) {
    $form->renderActive(null, null, $self_url, 'post');
}

require HORDE_TEMPLATES . '/common-footer.inc';
