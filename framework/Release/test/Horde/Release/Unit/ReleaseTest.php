<?php
/**
 * Test the version processing.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Release
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Release
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the version processing.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Release
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Release
 */
class Horde_Release_Unit_ReleaseTest
extends Horde_Release_TestCase
{
    public function testUpdateSentinel()
    {
        $tmp_dir = $this->getTemporaryDirectory();
        $r = $this->_getReleaseHelper(
            $this->_getOptions(
                array('oldversion' => '0.9', 'version' => '1.0',)
            )
        );
        mkdir($tmp_dir . '/docs');
        file_put_contents($tmp_dir . '/docs/CHANGES', "\n=OLD=\n");

        $r->setVersionStrings();
        $r->setDirectoryName($tmp_dir);
        ob_start();
        $r->updateSentinel();
        ob_end_clean();

        $this->assertEquals(
            '----------
v1.0.1-cvs
----------





=OLD=
',
            file_get_contents($tmp_dir . '/docs/CHANGES')
        );
    }

    private function _getOptions($options)
    {
        return array_merge(
            array(
                'module' => 'test',
                'branch' => 'master',
                'horde' => array(
                    'user' => 'NONE'
                )
            ),
            $options
        );
    }

    private function _getReleaseHelper($options)
    {
        ob_start();
        $r = new Horde_Release_Stub_Release($options);
        ob_get_clean();
        return $r;
    }
}
