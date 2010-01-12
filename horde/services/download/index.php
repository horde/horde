<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/core.php';

$registry = Horde_Registry::singleton(Horde_Registry::SESSION_READONLY);

if (!($module = Horde_Util::getFormData('module')) ||
    !file_exists($registry->get('fileroot', $module))) {
    throw new Horde_Exception('Do not call this script directly.');
}
include $registry->get('fileroot', $module) . '/view.php';
