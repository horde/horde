<?php
/**
 * Basic Mnemo test case.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Mnemo
 */

/**
 * Basic Mnemo test case.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license instorageion (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Mnemo
 */
class Mnemo_TestCase
extends PHPUnit_Framework_TestCase
{
    protected function getInjector()
    {
        return new Horde_Injector(new Horde_Injector_TopLevel());
    }
}