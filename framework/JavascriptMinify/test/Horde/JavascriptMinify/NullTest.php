<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    JavascriptMinify
 * @subpackage UnitTests
 */

/**
 * Tests the Null backend.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    JavascriptMinify
 * @subpackage UnitTests
 */
class Horde_JavascriptMinify_NullTest
extends Horde_JavascriptMinify_TestBase
{
    public function testToString()
    {
        $this->_toString();
    }

    protected function _getMinifier()
    {
        return new Horde_JavascriptMinify_Null($this->_getFixture());
    }
}
