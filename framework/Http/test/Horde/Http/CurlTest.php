<?php
/**
 * Copyright 2007-2015 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Http
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Copyright 2007-2015 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Http
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Http_CurlTest extends Horde_Http_TestBase
{
    public function setUp()
    {
        if (!function_exists('curl_exec')) {
            $this->markTestSkipped('Missing PHP extension "curl"!');
        }
        parent::setUp();
    }
}
