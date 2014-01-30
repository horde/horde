<?php
/**
 * @category Horde
 * @package  Autoloader
 */
class Horde_Autoloader_ClassPathMapper_PrefixStringTest
extends PHPUnit_Framework_TestCase
{
    private $_mapper;

    public function setUp()
    {
        $this->_mapper = new Horde_Autoloader_ClassPathMapper_PrefixString(
            'App',
            'dir'
        );
    }

    public function providerClassNames()
    {
        return array(
            array('App',         'dir/App.php'),
            array('App_Foo',     'dir/Foo.php'),
            array('App_Foo_Bar', 'dir/Foo/Bar.php'),
            array('App\Foo\Bar', 'dir/Foo/Bar.php'),
            array('app_foo',     'dir/foo.php')
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
