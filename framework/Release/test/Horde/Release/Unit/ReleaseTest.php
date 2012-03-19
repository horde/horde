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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Release
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the version processing.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Release
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
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

    public function testSetMailingList()
    {
        $r = $this->_getAnnounceHelper();
        $r->notes['list'] = 'newlist@lists.horde.org';

        $this->assertContains(
            'newlist@lists.horde.org',
            $this->_announce($r)
        );
    }

    public function testHordeMailingList()
    {
        $r = $this->_getAnnounceHelper(array('module' => 'horde-something'));
        $this->assertContains(
            'horde@lists.horde.org',
            $this->_announce($r)
        );
    }

    public function testNoI18NForFinal()
    {
        $r = $this->_getAnnounceHelper();
        $this->assertNotContains(
            'i18n@lists.horde.org',
            $this->_announce($r)
        );
    }

    public function testI18NForPreRelease()
    {
        $r = $this->_getAnnounceHelper(array('version' => '1.0-RC1'));
        $this->assertContains(
            'i18n@lists.horde.org',
            $this->_announce($r)
        );
    }

    public function testSubject()
    {
        $r = $this->_getAnnounceHelper();
        $this->assertContains(
            'Horde 1.0',
            $this->_announce($r)
        );
    }

    public function testSubjectForFinal()
    {
        $r = $this->_getAnnounceHelper();
        $this->assertContains(
            '(final)',
            $this->_announce($r)
        );
    }

    public function testSubjectForPreRelease()
    {
        $r = $this->_getAnnounceHelper(array('version' => '1.0-RC1'));
        $this->assertNotContains(
            '(final)',
            $this->_announce($r)
        );
    }

    public function testSubjectForBranch()
    {
        $r = $this->_getAnnounceHelper(array('version' => 'H4-1.0-RC1'));
        $this->assertContains(
            'Horde H4 (1.0-RC1)',
            $this->_announce($r)
        );
    }

    public function testSubjectForFinalBranch()
    {
        $r = $this->_getAnnounceHelper(array('version' => 'H4-1.0'));
        $this->assertContains(
            'Horde H4 (1.0) (final)',
            $this->_announce($r)
        );
    }

    public function testSecuritySubject()
    {
        $r = $this->_getAnnounceHelper();
        $r->notes['fm']['focus'] = Horde_Release::FOCUS_MAJORSECURITY;
        $this->assertContains(
            '[SECURITY]',
            $this->_announce($r)
        );
    }

    public function testSender()
    {
        $r = $this->_getAnnounceHelper();
        $this->assertContains(
            'test@example.com',
            $this->_announce($r)
        );
    }

    public function testChangelog()
    {
        $r = $this->_getAnnounceHelper();
        $this->assertContains(
            '(from version 0.9)',
            $this->_announce($r)
        );
    }

    public function testChangeUrl()
    {
        $r = $this->_getAnnounceHelper();
        $this->assertContains(
            'http://cvs.horde.org/diff.php',
            $this->_announce($r)
        );
    }

    public function testMailingList()
    {
        $r = $this->_getAnnounceHelper();
        $this->assertContains(
            'ML-CHANGES',
            $this->_announce($r)
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

    private function _announce($r)
    {
        $r->setVersionStrings();
        ob_start();
        $r->announce();
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }


    private function _getAnnounceHelper($options = array())
    {
        $r = $this->_getReleaseHelper(
            array_merge(
                $this->_getOptions(
                    array(
                        'oldversion' => '0.9',
                        'version' => '1.0',
                        'noannounce' => true,
                        'ml' => array(
                            'from' => 'test@example.com'
                        )
                    )
                ),
                $options
            )
        );

        $r->notes['name'] = 'Horde';
        $r->notes['fm']['focus'] = 5;
        $r->notes['fm']['changes'] = 'FM-CHANGES';
        $r->notes['fm']['project'] = 'horde';
        $r->notes['fm']['branch'] = 'Horde 3';
        $r->notes['ml']['changes'] = 'ML-CHANGES';
        return $r;
    }

    private function _getReleaseHelper($options)
    {
        ob_start();
        $r = new Horde_Release_Stub_Release($options);
        ob_get_clean();
        return $r;
    }
}
