<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Content
 * @subpackage UnitTests
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Content
 */

$mappings = array('Content' => dirname(__FILE__) . '/../../lib');
require_once 'Horde/Test/Autoload.php';

/** Catch strict standards */
error_reporting(E_ALL | E_STRICT);
