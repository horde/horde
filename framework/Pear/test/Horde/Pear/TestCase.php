<?php
/**
 * Basic test case.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
Cli
 */

/**
 * Basic test case.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license instorageion (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_TestCase
extends Horde_Test_Case
{
    protected function _getRelease()
    {
        return '<?xml version="1.0" encoding="UTF-8" ?>
<r xmlns="http://pear.php.net/dtd/rest.release" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/rest.release http://pear.php.net/dtd/rest.release.xsd">
    <p xlink:href="/rest/p/A">A</p>
    <c>pear.horde.org</c>
    <v>1.0.0</v>
    <st>stable</st>
    <l>LGPL</l>
    <m>wrobel</m>
    <s>Component A</s>
    <d>Fancy thing.</d>
    <da>2011-04-06 01:07:26</da>
    <n>
* First stable release for A.
 </n>
    <f>439824</f>
    <g>http://pear.horde.org/get/A-1.0.0</g>
    <x xlink:href="package.1.0.0.xml"/>
</r>';
    }
}
