<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @license    http://www.horde.org/licenses/apache ASL
 * @package    Ingo
 * @subpackage UnitTests
 */

/**
 * Common library for Ingo test cases
 *
 * @author     Jason M. Felice <jason.m.felice@gmail.com>
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/apache ASL
 * @package    Ingo
 * @subpackage UnitTests
 */

class Ingo_Unit_TestBase extends PHPUnit_Framework_TestCase
{
    protected $script;
    protected $storage;

    public function setUp()
    {
        $injector = $this->getMock('Horde_Injector', array(), array(), '', false);
        $injector->expects($this->any())
            ->method('getInstance')
            ->will($this->returnCallback(array($this, '_injectorGetInstance')));
        $GLOBALS['injector'] = $injector;

        $prefs = $this->getMock('Horde_Prefs', array(), array(), '', false);
        $prefs->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue(false));
        $GLOBALS['prefs'] = $prefs;

        $registry = $this->getMock('Horde_Registry', array(), array(), '', false);
        $registry->expects($this->any())
            ->method('hasMethod')
            ->will($this->returnValue(true));
        $GLOBALS['registry'] = $registry;

        $GLOBALS['session'] = $this->getMock('Horde_Session');

        if (!defined('INGO_BASE')) {
            define('INGO_BASE', realpath(__DIR__ . '/../../..'));
        }

        $this->storage = new Ingo_Storage_Memory();

        $GLOBALS['conf']['spam'] = array(
            'enabled' => true,
            'char' => '*',
            'header' => 'X-Spam-Level'
        );
    }

    public function _injectorGetInstance($interface)
    {
        switch ($interface) {
        case 'Horde_Core_Factory_Identity':
            $identity = $this->getMock('Horde_Core_Prefs_Identity', array(), array(), '', false);
            $identity->expects($this->any())
                ->method('getName')
                ->will($this->returnValue('Foo'));
            $identity->expects($this->any())
                ->method('getDefaultFromAddress')
                ->will($this->returnValue('foo@example.com'));
            $identity->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue('XYZ'));

            $factory = $this->getMock($interface, array(), array(), '', false);
            $factory->expects($this->any())
                ->method('create')
                ->will($this->returnValue($identity));

            return $factory;

        case 'Horde_Core_Hooks':
            $hooks = $this->getMock(
                'Horde_Core_Hooks', array(), array(), '', false
            );
            $hooks->expects($this->any())
                ->method('callHook')
                ->will($this->returnCallback(array($this, '_hooksCallback')));

            return $hooks;

        case 'Horde_Core_Perms':
            $perms = $this->getMock('Horde_Core_Perms', array(), array(), '', false);
            $perms->method('hasAppPermission')->will($this->returnValue(true));
            return $perms;
        }
    }

    public function _hooksCallback()
    {
        throw new Horde_Exception_HookNotSet();
    }

    protected function _assertScript($expect)
    {
        $result = $this->script->generate();
        if (empty($result[0]['script'])) {
            $this->fail("result not a script", 1);
            return;
        }

        /* Remove comments and crunch whitespace so we can have a functional
         * comparison. */
        $new = array();
        foreach (explode("\n", $result[0]['script']) as $line) {
            if (preg_match('/^\s*$/', $line)) {
                continue;
            }
            if (preg_match('/^\s*#.*$/', $line)) {
                continue;
            }
            $new[] = trim($line);
        }

        $new_script = join("\n", $new);
        $this->assertEquals($expect, $new_script);
    }

}
