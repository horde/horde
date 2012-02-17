<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage UnitTests
 */

/**
 * @group      view
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage UnitTests
 */
class Horde_View_Helper_DebugTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->helper = new Horde_View_Helper_Debug(new Horde_View());
    }

    // test truncate
    public function testDebug()
    {
        $expected = '<pre class="debug_dump">string(7) &quot;foo&amp;bar&quot;';
        $this->assertContains($expected, $this->helper->debug('foo&bar'));
    }

}
