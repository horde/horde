<?php
class Horde_Injector_Binder_FactoryTest extends Horde_Test_Case
{
    public function testShouldCallFactoryMethod()
    {
        $factory = $this->getMockSkipConstructor('Horde_Injector_Binder_Factory', array('create'));
        $factory->expects($this->once())
            ->method('create')
            ->with()
            ->will($this->returnValue('INSTANCE'));
        $factoryClassName = get_class($factory);

        $childInjector = $this->getMockSkipConstructor('Horde_Injector', array('createInstance', 'getInstance'));
        $childInjector->expects($this->once())
            ->method('getInstance')
            ->with($this->equalTo($factoryClassName))
            ->will($this->returnValue($factory));

        $injector = $this->getMockSkipConstructor('Horde_Injector', array('createChildInjector'));
        $injector->expects($this->once())
            ->method('createChildInjector')
            ->with()
            ->will($this->returnValue($childInjector));

        $factoryBinder = new Horde_Injector_Binder_Factory(
            $factoryClassName,
            'create'
        );

        $this->assertEquals('INSTANCE', $factoryBinder->create($injector));
    }

    /**
     * the factory binder should pass a child injector object to the factory, so that
     * any configuration that happens in the factory will not bleed into global scope
     */
    public function testShouldPassChildInjectorToFactoryMethod()
    {
        $factory = new InjectorFactoryTestMockFactory();

        $binder = new Horde_Injector_Binder_Factory('InjectorFactoryTestMockFactory', 'create');

        $injector = new InjectorMockTestAccess(new Horde_Injector_TopLevel());
        $injector->TEST_ID = "PARENTINJECTOR";

        // set the instance so we know we'll get our factory object from the injector
        $injector->setInstance('InjectorFactoryTestMockFactory', $factory);

        // calling create should pass a child injector to the factory
        $binder->create($injector);

        // now the factory should have a reference to a child injector
        $this->assertEquals($injector->TEST_ID . "->CHILD", $factory->getInjector()->TEST_ID, "Incorrect Injector passed to factory method");
    }

    /**
     * this test guarantees that our mock factory stores the injector that was given to it,
     * so that we may inspect it later and prove what injector is actually given to it
     */
    public function testMockFactoryStoresPassedInjector()
    {
        $factory = new InjectorFactoryTestMockFactory();
        $injector = new InjectorMockTestAccess(new Horde_Injector_TopLevel());
        $injector->TEST_ID = "INJECTOR";
        $factory->create($injector);

        $this->assertEquals($injector, $factory->getInjector());
    }

    public function testShouldReturnBindingDetails()
    {
        $factoryBinder = new Horde_Injector_Binder_Factory(
            'FACTORY',
            'METHOD'
        );

        $this->assertEquals('FACTORY', $factoryBinder->getFactory());
        $this->assertEquals('METHOD', $factoryBinder->getMethod());
    }
}

class InjectorFactoryTestMockFactory
{
    public function getInjector()
    {
        return $this->_injector;
    }
    public function create(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }
}
class InjectorMockTestAccess extends Horde_Injector
{
    public function createChildInjector()
    {
        $child = new self($this);
        $child->TEST_ID = $this->TEST_ID . "->CHILD";
        return $child;
    }
}
