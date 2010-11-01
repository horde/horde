<?php
/**
 * @category Horde
 * @package  Horde_Autoloader
 */
class Horde_Autoloader_ClassPathMapper_ApplicationTest extends PHPUnit_Framework_TestCase
{
    private $_mapper;

    public function setUp()
    {
        $this->_mapper = new Horde_Autoloader_ClassPathMapper_Application(
            'app' // directory to app dir
        );
        $this->_mapper->addMapping('Suffix', 'subdir');
    }

    public function providerValidClassNames()
    {
        return array(
            array('Module_Action_Suffix', 'app/subdir/Action.php'),
            array('MyModule_Action_Suffix', 'app/subdir/Action.php'),
            array('Module_MyAction_Suffix', 'app/subdir/MyAction.php'),
            array('MyModule_MyAction_Suffix', 'app/subdir/MyAction.php'),
        );
    }

    /**
     * @dataProvider providerValidClassNames
     */
    public function testShouldMapValidAppClassToAppPath($validClassName, $classPath)
    {
        $this->assertEquals(
            $classPath,
            $this->_mapper->mapToPath($validClassName)
        );
    }

    public function providerInvalidClassNames()
    {
        return array(
            array('Module_Action_BadSuffix'),
            array('module_Action_Suffix'),
            array('Module_action_Suffix'),
            array('Module-Action-Suffix'),
            array(''),
        );
    }

    /**
     * @dataProvider providerInvalidClassNames
     */
    public function testShouldIgnoreInvalidAppClassNames($invalidClassName)
    {
        $this->assertNull($this->_mapper->mapToPath($invalidClassName));
    }
}
