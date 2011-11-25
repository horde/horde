<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/TestBase.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Vcs
 * @subpackage UnitTests
 */

class Horde_Vcs_CvsTest extends Horde_Vcs_TestBase
{
    public function setUp()
    {
        if (!self::$conf) {
            $this->markTestSkipped();
        }
        $this->vcs = Horde_Vcs::factory(
            'Cvs',
            array_merge(self::$conf,
                        array('sourceroot' => dirname(__FILE__) . '/repos/cvs')));
    }

    public function testFactory()
    {
        $this->assertInstanceOf('Horde_Vcs_Cvs', $this->vcs);
        $this->assertTrue($this->vcs->hasFeature('branches'));
        $this->assertTrue($this->vcs->hasFeature('deleted'));
        $this->assertTrue($this->vcs->hasFeature('patchsets'));
        $this->assertFalse($this->vcs->hasFeature('snapshots'));
        $this->assertFalse($this->vcs->hasFeature('foo'));
    }
}
