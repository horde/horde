<?php
/**
 * $Id: block.php 219 2008-01-11 09:45:33Z duck $
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

$block_data = array();
$block = Horde_Block_Collection::getBlock($app, $name, Horde_Util::getFormData('defaults'));
if ($block instanceof PEAR_Error) {
    $block_data['title'] = $block->getMessage();
    $block_data['content'] = $block->getDebugInfo();
} else {
    $block_data['title'] = @$block->getTitle();
    if ($block_data['title'] instanceof PEAR_Error) {
        $block_data['title'] = $block_data['title']->getMessage();
    }
    $block_data['content'] = @$block->getContent();
    if ($block_data['content'] instanceof PEAR_Error) {
        $block_data['content'] = $block_data['content']->getDebugInfo();
    }
}

echo Horde_Serialize::serialize($block_data, Horde_Serialize::JSON, 'UTF-8');
