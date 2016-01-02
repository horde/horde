<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
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
class Horde_Http_PeclhttpTest extends Horde_Http_TestBase
{
    public function setUp()
    {
        if (!class_exists('HttpRequest', false)) {
            $this->markTestSkipped('Missing PHP extension "http" or wrong version!');
        }
        parent::setUp();
    }
}
