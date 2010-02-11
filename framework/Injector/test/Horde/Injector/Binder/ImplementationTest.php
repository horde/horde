<?php
class Horde_Injector_Binder_ImplementationTest extends Horde_Test_Case
{
    public function testShouldReturnBindingDetails()
    {
        $implBinder = new Horde_Injector_Binder_Implementation(
            'IMPLEMENTATION'
        );

        $this->assertEquals('IMPLEMENTATION', $implBinder->getImplementation());
    }

    public function testShouldCreateInstanceOfClassWithNoDependencies()
    {
        $implBinder = new Horde_Injector_Binder_Implementation(
            'Horde_Injector_Binder_ImplementationTest__NoDependencies'
        );

        $this->assertType(
            'Horde_Injector_Binder_ImplementationTest__NoDependencies',
            $implBinder->create($this->_getInjectorNeverCallMock())
        );
    }

    public function testShouldCreateInstanceOfClassWithTypedDependencies()
    {
        $implBinder = new Horde_Injector_Binder_Implementation(
            'Horde_Injector_Binder_ImplementationTest__TypedDependency'
        );

        $createdInstance = $implBinder->create($this->_getInjectorReturnsNoDependencyObject());

        $this->assertType(
            'Horde_Injector_Binder_ImplementationTest__TypedDependency',
            $createdInstance
        );

        $this->assertType(
            'Horde_Injector_Binder_ImplementationTest__NoDependencies',
            $createdInstance->dep
        );
    }

    /**
     * @expectedException Horde_Injector_Exception
     */
    public function testShouldThrowExceptionWhenTryingToCreateInstanceOfClassWithUntypedDependencies()
    {
        $implBinder = new Horde_Injector_Binder_Implementation(
            'Horde_Injector_Binder_ImplementationTest__UntypedDependency'
        );

        $implBinder->create($this->_getInjectorNeverCallMock());
    }

    public function testShouldUseDefaultValuesFromUntypedOptionalParameters()
    {
        $implBinder = new Horde_Injector_Binder_Implementation(
            'Horde_Injector_Binder_ImplementationTest__UntypedOptionalDependency'
        );

        $createdInstance = $implBinder->create($this->_getInjectorNeverCallMock());

        $this->assertEquals('DEPENDENCY', $createdInstance->dep);
    }

    /**
     * @expectedException ReflectionException
     */
    public function testShouldThrowExceptionIfRequestedClassIsNotDefined()
    {
        $implBinder = new Horde_Injector_Binder_Implementation(
            'CLASS_DOES_NOT_EXIST'
        );

        $implBinder->create($this->_getInjectorNeverCallMock());
    }

    /**
     * @expectedException Horde_Injector_Exception
     */
    public function testShouldThrowExcpetionIfImplementationIsAnInterface()
    {
        $implBinder = new Horde_Injector_Binder_Implementation(
            'Horde_Injector_Binder_ImplementationTest__Interface'
        );

        $implBinder->create($this->_getInjectorNeverCallMock());
    }

    /**
     * @expectedException Horde_Injector_Exception
     */
    public function testShouldThrowExcpetionIfImplementationIsAnAbstractClass()
    {
        $implBinder = new Horde_Injector_Binder_Implementation(
            'Horde_Injector_Binder_ImplementationTest__AbstractClass'
        );

        $implBinder->create($this->_getInjectorNeverCallMock());
    }

    public function testShouldCallSetterMethodsWithNoDependenciesIfRequested()
    {
        $implBinder = new Horde_Injector_Binder_Implementation(
            'Horde_Injector_Binder_ImplementationTest__SetterNoDependencies'
        );
        $implBinder->bindSetter('setDependency');

        $instance = $implBinder->create($this->_getInjectorNeverCallMock());

        $this->assertType(
            'Horde_Injector_Binder_ImplementationTest__SetterNoDependencies',
            $instance
        );

        $this->assertEquals('CALLED', $instance->setterDep);
    }

    public function testShouldCallSetterMethodsWithDependenciesIfRequested()
    {
        $implBinder = new Horde_Injector_Binder_Implementation(
            'Horde_Injector_Binder_ImplementationTest__SetterHasDependencies'
        );
        $implBinder->bindSetter('setDependency');

        $instance = $implBinder->create($this->_getInjectorReturnsNoDependencyObject());

        $this->assertType(
            'Horde_Injector_Binder_ImplementationTest__SetterHasDependencies',
            $instance
        );

        $this->assertType(
            'Horde_Injector_Binder_ImplementationTest__NoDependencies',
            $instance->setterDep
        );
    }

    private function _getInjectorNeverCallMock()
    {
        $injector = $this->getMockSkipConstructor('Horde_Injector', array('getInstance'));
        $injector->expects($this->never())
            ->method('getInstance');
        return $injector;
    }

    private function _getInjectorReturnsNoDependencyObject()
    {
        $injector = $this->getMockSkipConstructor('Horde_Injector', array('getInstance'));
        $injector->expects($this->once())
            ->method('getInstance')
            ->with($this->equalTo('Horde_Injector_Binder_ImplementationTest__NoDependencies'))
            ->will($this->returnValue(new Horde_Injector_Binder_ImplementationTest__NoDependencies()));
        return $injector;
    }
}

/**
 * Used by preceeding tests!!!
 */

class Horde_Injector_Binder_ImplementationTest__NoDependencies
{
}

class Horde_Injector_Binder_ImplementationTest__TypedDependency
{
    public $dep;

    public function __construct(Horde_Injector_Binder_ImplementationTest__NoDependencies $dep)
    {
        $this->dep = $dep;
    }
}

class Horde_Injector_Binder_ImplementationTest__UntypedDependency
{
    public function __construct($dep)
    {
    }
}

class Horde_Injector_Binder_ImplementationTest__UntypedOptionalDependency
{
    public $dep;

    public function __construct($dep = 'DEPENDENCY')
    {
        $this->dep = $dep;
    }
}

interface Horde_Injector_Binder_ImplementationTest__Interface
{
}

abstract class Horde_Injector_Binder_ImplementationTest__AbstractClass
{
}

class Horde_Injector_Binder_ImplementationTest__SetterNoDependencies
{
    public $setterDep;

    public function setDependency()
    {
        $this->setterDep = 'CALLED';
    }
}

class Horde_Injector_Binder_ImplementationTest__SetterHasDependencies
{
    public $setterDep;

    public function setDependency(Horde_Injector_Binder_ImplementationTest__NoDependencies $setterDep)
    {
        $this->setterDep = $setterDep;
    }
}
