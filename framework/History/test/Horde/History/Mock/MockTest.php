<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @package    History
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_History_Mock_MockTest extends Horde_History_TestBase
{
    protected function setUp()
    {
        self::$history = new Horde_History_Mock('test');
    }

    protected function tearDown()
    {
        self::$history = null;
    }

}
