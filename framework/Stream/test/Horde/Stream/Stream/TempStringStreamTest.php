<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Stream
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Stream_TempString class, with the data being stored
 * in a PHP temp stream internally.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Stream
 * @subpackage UnitTests
 */
class Horde_Stream_Stream_TempStringStreamTest
extends Horde_Stream_Stream_TestBase
{
    protected function _getOb()
    {
        return new Horde_Stream_TempString(array(
            'max_memory' => 1
        ));
    }

    public function testUsingStream()
    {
        $ob = $this->_getOb();
        $ob->add('123');

        $this->assertTrue($ob->use_stream);
    }

}
