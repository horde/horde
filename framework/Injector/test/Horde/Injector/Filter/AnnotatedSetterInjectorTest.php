<?php
class Horde_Injector_Filter_AnnotatedSetterInjectorTest extends PHPUnit_Framework_TestCase
{
    public function testAnnotatedSettersAreInjected()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $foo1 = $injector->createInstance('Horde_Injector_TestFoo');
        $this->assertNull($foo1->bar);

        $injector->addFilter(new Horde_Injector_Filter_AnnotatedSetterInjector());
        $foo2 = $injector->createInstance('Horde_Injector_TestFoo');
        $this->assertType('Horde_Injector_TestBar', $foo2->bar);
    }

    public function testAnnotatedSettersAreThereWhenCallingGetInstanceAgain()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $injector->addFilter(new Horde_Injector_Filter_AnnotatedSetterInjector());
        $foo1 = $injector->getInstance('Horde_Injector_TestFoo');

        $foo2 = $injector->getInstance('Horde_Injector_TestFoo');
        $this->assertType('Horde_Injector_TestBar', $foo2->bar);
    }
}

/**
 * Used by the preceding tests
 *
 * @inject setBar
 */
class Horde_Injector_TestFoo
{
    public $bar;

    public function setBar(Horde_Injector_TestBar $bar)
    {
        $this->bar = $bar;
    }
}

class Horde_Injector_TestBar
{
}
