<?php
/**
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */

/**
 * @group      view
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */
class Horde_View_InterfaceTest extends PHPUnit_Framework_TestCase {

    public function testViewInterface()
    {
        eval('class Test_View extends Horde_View implements Horde_View_Interface {};');
    }

}
