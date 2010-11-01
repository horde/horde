<?php
class Horde_Injector_Binder_ClosureTest extends Horde_Test_Case
{
    public function testShouldCallClosure()
    {
        $childInjector = $this->getMockSkipConstructor('Horde_Injector', array('createInstance', 'getInstance'));
        $injector = $this->getMockSkipConstructor('Horde_Injector', array('createChildInjector'));
        $injector->expects($this->once())
            ->method('createChildInjector')
            ->with()
            ->will($this->returnValue($childInjector));

        $closureBinder = new Horde_Injector_Binder_Closure(
            function (Horde_Injector $injector) { return 'INSTANCE'; }
        );

        $this->assertEquals('INSTANCE', $closureBinder->create($injector));
    }

    /**
     * The closure binder should pass a child injector object to the closure, so that
     * any configuration that happens in the closure will not bleed into global scope
     */
    public function testShouldPassChildInjectorToClosure()
    {
        $closure = function (Horde_Injector $injector) { return $injector; };

        $binder = new Horde_Injector_Binder_Closure($closure);

        $injector = new ClosureInjectorMockTestAccess(new Horde_Injector_TopLevel());
        $injector->TEST_ID = "PARENTINJECTOR";

        // calling create should pass a child injector to the factory
        $childInjector = $binder->create($injector);

        // now the factory should have a reference to a child injector
        $this->assertEquals($injector->TEST_ID . "->CHILD", $childInjector->TEST_ID, "Incorrect Injector passed to closure");
    }

    public function testShouldReturnBindingDetails()
    {
        $closure = function (Horde_Injector $injector) {};
        $closureBinder = new Horde_Injector_Binder_Closure(
            $closure
        );

        $this->assertEquals($closure, $closureBinder->getClosure());
    }
}

class ClosureInjectorMockTestAccess extends Horde_Injector
{
    public function createChildInjector()
    {
        $child = new self($this);
        $child->TEST_ID = $this->TEST_ID . "->CHILD";
        return $child;
    }
}
