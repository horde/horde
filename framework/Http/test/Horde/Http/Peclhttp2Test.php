<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Http
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Unit tests for version 1.x of the PECL http extension.
 *
 * @category   Horde
 * @package    Http
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Http_Peclhttp2Test extends Horde_Http_TestBase
{
    public function setUp()
    {
        if (!class_exists('\http\Client', false)) {
            $this->markTestSkipped('Missing PHP extension "http" or wrong version!');
        }
        parent::setUp();
    }
}
