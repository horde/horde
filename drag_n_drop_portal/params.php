<?php
/**
 * $Id: params.php 216 2008-01-10 19:47:31Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
define('HORDE_BASE', dirname(__FILE__) . '/..');
require_once HORDE_BASE . '/lib/base.php';
require_once 'Horde/Loader.php';

// Block to load
$block_id = Horde_Util::getFormData('block');
list($app, $name) = explode(':', $block_id);

// Load collection
$blocks = new Horde_Block_Collection(null, array($app));

// Create block params form
$params = $blocks->getParams($app, $name);
if (empty($params) ||
    !$blocks->isEditable($app, $name)) {
    echo '<script type="text/javascript">noParams("' . Horde_Util::getFormData('widget') . '", "' . _("This block has no special parameters.") . '");</script>';
} else {
    $block = &$blocks->getBlock($app, $name);

    $defaults = Horde_Util::getFormData('defaults');
    if (empty($defaults)) {
        foreach ($params as $key => $val) {
            $defaults[$key] = $val;
        }
    }
    if (!isset($defaults['_refresh_time'])) {
        $defaults['_refresh_time'] = 0;
    }
    require './templates/portal/params.php';
}
