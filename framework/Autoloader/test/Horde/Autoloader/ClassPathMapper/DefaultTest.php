<?php
/**
 * @category Horde
 * @package  Horde_Autoloader
 */
class Horde_Autoloader_ClassPathMapper_DefaultTest extends PHPUnit_Framework_TestCase
{
    private $_mapper;

    public function setUp()
    {
        $this->_mapper = new Horde_Autoloader_ClassPathMapper_Default('dir');
    }

    public function providerClassNames()
    {
        return array(
            array('Module_Action_Suffix', 'dir/Module/Action/Suffix.php'),
            array('MyModule_Action_Suffix', 'dir/MyModule/Action/Suffix.php'),
            array('Module_MyAction_Suffix', 'dir/Module/MyAction/Suffix.php'),
            array('MyModule_MyAction_Suffix', 'dir/MyModule/MyAction/Suffix.php'),
            array('Module\Action\Suffix', 'dir/Module/Action/Suffix.php'),
            array('MyModule\Action\Suffix', 'dir/MyModule/Action/Suffix.php'),
            array('Module\MyAction\Suffix', 'dir/Module/MyAction/Suffix.php'),
            array('MyModule\MyAction\Suffix', 'dir/MyModule/MyAction/Suffix.php'),
        );
    }

    /**
     * @dataProvider providerClassNames
     */
    public function testShouldMapClassToPath($className, $classPath)
    {
        $this->assertEquals(
            $classPath,
            $this->_mapper->mapToPath($className)
        );
    }
}
