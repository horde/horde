<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'authentication' => 'none',
    'session_control' => 'readonly'
));

if (!($module = Horde_Util::getFormData('module')) ||
    !file_exists($registry->get('fileroot', $module))) {
    throw new Horde_Exception('Do not call this script directly.');
}

$registry->pushApp($module);
include $registry->get('fileroot', $module) . '/view.php';
