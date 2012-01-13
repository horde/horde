<?php
/**
 * Horde test case helper.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Test
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */

/**
 * Horde test case helper.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Test
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */
class Horde_Test_Functional extends Horde_Test_Case
{
    /**
     * Test two XML strings for equivalency (e.g., identical up to reordering of
     * attributes).
     */
    public function assertDomEquals($expected, $actual, $message = null)
    {
        $expectedDom = new DOMDocument();
        $expectedDom->loadXML($expected);

        $actualDom = new DOMDocument();
        $actualDom->loadXML($actual);

        $this->assertEquals($expectedDom->saveXML(), $actualDom->saveXML(), $message);
    }

    /**
     * Test two HTML strings for equivalency (e.g., identical up to reordering
     * of attributes).
     */
    public function assertHtmlDomEquals($expected, $actual, $message = null)
    {
        $expectedDom = new DOMDocument();
        $expectedDom->loadHTML($expected);

        $actualDom = new DOMDocument();
        $actualDom->loadHTML($actual);

        $this->assertEquals($expectedDom->saveHTML(), $actualDom->saveHTML(), $message);
    }
}
