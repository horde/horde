<?php
/**
 * Test Kolab Format MIME attributes
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test Kolab Format MIME attributes
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Integration_MimeAttrTest
extends PHPUnit_Framework_TestCase
{
    /**
     * Set up testing.
     *
     * @return NULL
     */
    protected function setUp()
    {
        $GLOBALS['registry']->setCharset('utf-8');
    }

    /**
     * Test retrieving the document name.
     *
     * @return NULL
     */
    public function testGetName()
    {
        $format = Horde_Kolab_Format::factory('XML', 'contact');
        $this->assertEquals('kolab.xml', $format->getName());
    }

    /**
     * Test retrieving the document mime type.
     *
     * @return NULL
     */
    public function testMimeType()
    {
        $format = Horde_Kolab_Format::factory('XML', 'contact');
        $this->assertEquals('application/x-vnd.kolab.contact',
                            $format->getMimeType());
    }

    /**
     * Test retrieving the document disposition.
     *
     * @return NULL
     */
    public function testGetDisposition()
    {
        $format = Horde_Kolab_Format::factory('XML', 'contact');
        $this->assertEquals('attachment', $format->getDisposition());
    }
}
