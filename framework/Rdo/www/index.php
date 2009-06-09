<?php
/**
 * Select testing backend and testing table
 *
 * Copyright 2006-2007 Duck <duck@obala.net>
 *
 * $Horde: incubator/Horde_Rdo/index.php,v 1.2 2007/06/27 17:23:28 jan Exp $
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @since   Horde 4.0
 * @package Horde_Rdo
 */

@define('HORDE_BASE', dirname(__FILE__) . '/../../');
require_once HORDE_BASE . '/lib/base.php';

if (!Auth::isAdmin()) {
    die('Permission denied');
}

require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Array.php';

$vars = Horde_Variables::getDefaultVariables();

$dbh = DB::connect($conf['sql']);
$tables = $dbh->getListOf('tables');
$tables = Horde_Array::valuesToKeys($tables);

$actions = array('create' => _("Create"),
                 'search' => _("Search"),
                 'search_active' => _("Search Active"));

$form = new Horde_Form($vars, _("Select table"), 'select_table');
$form->addVariable(_("Table"), 'table', 'enum', true, false, false, array($tables));
$form->addVariable(_("Action"), 'action', 'enum', true, false, false, array($actions));
$form->addVariable(_("Process type"), 'what2process', 'enum', true, false, false, array(array('Rdo' => 'Rdo',
                                                                                              'DB' => 'DB')));

require HORDE_TEMPLATES . '/common-header.inc';

$form->renderActive(null, null, 'crud.php', 'post');

require HORDE_TEMPLATES . '/common-footer.inc';
