<?php
class Horde_Injector_InjectorTest extends PHPUnit_Framework_TestCase
{
    public function testShouldGetDefaultImplementationBinder()
    {
        $topLevel = $this->getMock('Horde_Injector_TopLevel', array('getBinder'));
        $topLevel->expects($this->once())
            ->method('getBinder')
            ->with($this->equalTo('UNBOUND_INTERFACE'))
            ->will($this->returnValue('RETURNED_BINDING'));

        $injector = new Horde_Injector($topLevel);

        $this->assertEquals('RETURNED_BINDING', $injector->getBinder('UNBOUND_INTERFACE'));
    }

    public function testShouldGetManuallyBoundBinder()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $binder = new Horde_Injector_Binder_Mock();
        $injector->addBinder('BOUND_INTERFACE', $binder);
        $this->assertSame($binder, $injector->getBinder('BOUND_INTERFACE'));
    }

    public function testShouldProvideMagicFactoryMethodForBinderAddition()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());

        // binds a Horde_Injector_Binder_Mock object
        $this->assertType('Horde_Injector_Binder_Mock', $injector->bindMock('BOUND_INTERFACE'));
        $this->assertType('Horde_Injector_Binder_Mock', $injector->getBinder('BOUND_INTERFACE'));
    }

    public function testShouldProvideMagicFactoryMethodForBinderAdditionWhereBinderHasDependencies()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());

        // binds a Horde_Injector_Binder_Mock object
        $this->assertType('Horde_Injector_Binder_MockWithDependencies',
            $injector->bindMockWithDependencies('BOUND_INTERFACE', 'PARAMETER1'));
        $this->assertType('Horde_Injector_Binder_MockWithDependencies',
            $injector->getBinder('BOUND_INTERFACE'));
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testShouldThrowExceptionIfInterfaceNameIsNotPassedToMagicFactoryMethodForBinderAddition()
    {
        $injector = new Horde_Injector($this->_getTopLevelNeverCalledMock());
        $injector->bindMock();
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testShouldThrowExceptionIfMethodNameIsInvalid()
    {
        $injector = new Horde_Injector($this->_getTopLevelNeverCalledMock());
        $injector->invalid();
    }

    public function testShouldReturnItselfWhenInjectorRequested()
    {
        $injector = new Horde_Injector($this->_getTopLevelNeverCalledMock());
        $this->assertSame($injector, $injector->getInstance('Horde_Injector'));
    }

    /**
     * Would love to use PHPUnit's mock object here istead of Horde_Injector_Binder_Mock but you
     * can't be sure the expected resulting object is the same object you told the mock to return.
     * This is because Mock clone objects passed to mocked methods.
     *
     * http://www.phpunit.de/ticket/120
     *
     * @author Bob McKee <bmckee@bywires.com>
     */
    public function testCreateInstancePassesCurrentInjectorScopeToBinderForCreation()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $injector->addBinder('BOUND_INTERFACE', new Horde_Injector_Binder_Mock());

        // normally you wouldn't get an injector back; the binder would create something and return
        // it to you.  here we are just confirming that the proper injector was passed to the
        // binder's create method.
        $this->assertEquals($injector, $injector->createInstance('BOUND_INTERFACE'));
    }

    public function testShouldNotReturnSharedObjectOnCreate()
    {
        $injector = $this->_createInjector();
        //this call will cache this class on the injector
        $stdclass = $injector->getInstance('StdClass');

        $this->assertNotSame($stdclass, $injector->createInstance('StdClass'));
    }

    public function testShouldNotShareObjectCreatedUsingCreate()
    {
        $injector = $this->_createInjector();

        // this call should not store the instance on the injector
        $stdclass = $injector->createInstance('StdClass');

        $this->assertNotSame($stdclass, $injector->getInstance('StdClass'));
    }

    public function testChildSharesInstancesOfParent()
    {
        $injector = $this->_createInjector();

        //this call will store the created instance on $injector
        $stdclass = $injector->getInstance('StdClass');

        // create a child injector and ensure that the stdclass returned is the same
        $child = $injector->createChildInjector();
        $this->assertSame($stdclass, $child->getInstance('StdClass'));
    }

    private function _createInjector()
    {
        return new Horde_Injector(new Horde_Injector_TopLevel());
    }

    public function testShouldReturnSharedInstanceIfRequested()
    {
        $injector = new Horde_Injector($this->_getTopLevelNeverCalledMock());
        $instance = new StdClass();
        $injector->setInstance('INSTANCE_INTERFACE', $instance);
        $this->assertSame($instance, $injector->getInstance('INSTANCE_INTERFACE'));
    }

    /**
     * this test should test that when you override a binding in a child injector,
     * that the child does not create a new version of the object if the binding has not changed
     */
    public function testChildInjectorDoNotSaveBindingLocallyWhenBinderIsSameAsParent()
    {
        // we need to set a class for an instance on the parent
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $df = new Horde_Injector_DependencyFinder();
        $injector->addBinder('FooBarInterface', new Horde_Injector_Binder_Implementation('StdClass', $df));

        // getInstance will save $returnedObject and return it again later when FooBarInterface is requested
        $returnedObject = $injector->getInstance('FooBarInterface');

        $childInjector = $injector->createChildInjector();
        // add same binding again to child
        $childInjector->addBinder('FooBarInterface', new Horde_Injector_Binder_Implementation('StdClass', $df));

        $this->assertSame($returnedObject, $childInjector->getInstance('FooBarInterface'),
            "Child should have returned object reference from parent because added binder was identical to the parent binder");
    }

    /**
     * this test should test that when you override a binding in a child injector,
     * that the child creates a new version of the object, and not the parent's cached version
     * if the binding is changed
     */
    public function testChildInjectorsDoNotAskParentForInstanceIfBindingIsSet()
    {
        $mockTopLevel = $this->getMock('Horde_Injector_TopLevel', array('getInstance'));
        $mockTopLevel->expects($this->never())->method('getInstance');
        $injector = new Horde_Injector($mockTopLevel);

        $injector->addBinder('StdClass', new Horde_Injector_Binder_Mock());
        $injector->getInstance('StdClass');
    }

    public function testChildInjectorAsksParentForInstance()
    {
        $topLevelMock = $this->getMock('Horde_Injector_TopLevel', array('getInstance'));

        $topLevelMock->expects($this->once())
            ->method('getInstance')
            ->with('StdClass');

        $injector = new Horde_Injector($topLevelMock);

        $injector->getInstance('StdClass');
    }

    /**
     * Would love to use PHPUnit's mock object here istead of Horde_Injector_Binder_Mock but you
     * can't be sure the expected resulting object is the same object you told the mock to return.
     * This is because Mock clone objects passed to mocked methods.
     *
     * http://www.phpunit.de/ticket/120
     *
     * @author Bob McKee <bmckee@bywires.com>
     */
    public function testShouldCreateAndStoreSharedObjectIfOneDoesNotAlreadyExist()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $injector->addBinder('BOUND_INTERFACE', new Horde_Injector_Binder_Mock());

        // should call "createInstance" and then "setInstance" on the result
        // normally you wouldn't get an injector back; the binder would create something and return
        // it to you.  here we are just confirming that the proper injector was passed to the
        // binder's create method.
        $this->assertSame($injector, $injector->getInstance('BOUND_INTERFACE'));

        // should just return stored instance
        // the injector sent to the "create" method noted above should also be returned here.
        $this->assertSame($injector, $injector->getInstance('BOUND_INTERFACE'));
    }

    public function testShouldCreateAndStoreSharedObjectInstanceIfDefaultTopLevelBinderIsUsed()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());

        $class  = $injector->getInstance('StdClass');
        $class2 = $injector->getInstance('StdClass');

        $this->assertSame($class, $class2, "Injector did not return same object on consecutive getInstance calls");
    }

    public function testCreateChildInjectorReturnsDifferentInjector()
    {
        $injector = new Horde_Injector($this->_getTopLevelNeverCalledMock());
        $childInjector = $injector->createChildInjector();
        $this->assertType('Horde_Injector', $childInjector);
        $this->assertNotSame($injector, $childInjector);
    }

    public function testShouldAllowChildInjectorsAccessToParentInjectorBindings()
    {
        $mockInjector = $this->getMock('Horde_Injector_TopLevel', array('getBinder'));
        $mockInjector->expects($this->any()) // this gets called once in addBinder
            ->method('getBinder')
            ->with('BOUND_INTERFACE')
            ->will($this->returnValue(new Horde_Injector_Binder_Mock()));

        $injector = new Horde_Injector($mockInjector);
        $binder = new Horde_Injector_Binder_Mock();
        $injector->addBinder('BOUND_INTERFACE', $binder);
        $childInjector = $injector->createChildInjector();
        $this->assertSame($binder, $childInjector->getBinder('BOUND_INTERFACE'));
    }

    private function _getTopLevelNeverCalledMock()
    {
        $topLevel = $this->getMock('Horde_Injector_TopLevel', array('getBinder', 'getInstance'));
        $topLevel->expects($this->never())->method('getBinder');
        return $topLevel;
    }
}

/**
 * Used by preceding tests
 */
class Horde_Injector_Binder_Mock implements Horde_Injector_Binder
{
    private $_interface;
    public function create(Horde_Injector $injector)
    {
        return $injector;
    }

    public function equals(Horde_Injector_Binder $otherBinder)
    {
        return $otherBinder === $this;
    }
}

class Horde_Injector_Binder_MockWithDependencies implements Horde_Injector_Binder
{
    private $_interface;

    public function __construct($parameter1)
    {
    }

    public function create(Horde_Injector $injector)
    {
        return $injector;
    }

    public function equals(Horde_Injector_Binder $otherBinder)
    {
        return $otherBinder === $this;
    }
}
