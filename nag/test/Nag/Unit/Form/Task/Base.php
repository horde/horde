<?php
/**
 * Test the task form.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Test the task form.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Nag_Unit_Form_Task_Base extends Nag_TestCase
{
    /**
     * The test setup.
     *
     * @var Horde_Test_Setup
     */
    static $setup;

    private $_old_errorreporting;

    public static function setUpBeforeClass()
    {
        self::$setup = new Horde_Test_Setup();
        self::createBasicNagSetup(self::$setup);
        parent::setUpBeforeClass();
    }

    public function setUp()
    {
        $error = self::$setup->getError();
        if (!empty($error)) {
            $this->markTestSkipped($error);
        }

        $this->_old_errorreporting = error_reporting(E_ALL & ~(E_STRICT | E_DEPRECATED));
        error_reporting(E_ALL & ~(E_STRICT | E_DEPRECATED));

        parent::setUp();
    }

    public function tearDown()
    {
        error_reporting($this->_old_errorreporting);
        parent::tearDown();
    }

    public function testSingleAssignee()
    {
        $share = array_shift($GLOBALS['nag_shares']->listShares('test@example.com'));
        $vars = Horde_Variables::getDefaultVariables();
        $vars->set('tasklist_id', $share->getName());
        $form = new Nag_Form_Task($vars, _("New Task"));
        $this->assertEquals(
            array('test@example.com' => 'test@example.com'),
            $this->_getAssignees($form)
        );
    }

    public function testTwoAssignees()
    {
        $share = array_shift($GLOBALS['nag_shares']->listShares('test@example.com'));
        $share = $GLOBALS['nag_shares']->getShare($share->getName());
        $share->addUserPermission('jane', Horde_Perms::READ);
        $vars = Horde_Variables::getDefaultVariables();
        $vars->set('tasklist_id', $share->getName());
        $form = new Nag_Form_Task($vars, _("New Task"));
        $this->assertEquals(
            array('jane' => 'jane', 'test@example.com' => 'test@example.com'),
            $this->_getAssignees($form)
        );
    }

    private function _getAssignees($form)
    {
        $result = false;
        foreach ($form->getVariables() as $var) {
            if ($var->getVarName() == 'assignee') {
                $result = $var;
                break;
            }
        }
        $this->assertTrue($var !== false);
        return $var->getType()->getValues();
    }
}
