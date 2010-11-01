<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage UnitTests
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @group      horde_db
 * @category   Horde
 * @package    Horde_Db
 * @subpackage UnitTests
 */
class Horde_Db_Adapter_MissingTest extends PHPUnit_Framework_TestCase
{
    public function testMissingAdapter()
    {
        $this->markTestSkipped('The ' . $this->adapter . ' adapter is not available');
    }
}
