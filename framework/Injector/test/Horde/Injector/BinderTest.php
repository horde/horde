<?php
class Horde_Injector_BinderTest extends Horde_Test_Case
{
    /**
     * provider returns binder1, binder2, shouldEqual, errmesg
     */
    public function binderIsEqualProvider()
    {
        $df = new Horde_Injector_DependencyFinder();
        return array(
            array(
                new Horde_Injector_Binder_Implementation('foobar', $df),
                new Horde_Injector_Binder_Factory('factory', 'method'),
                false, "Implementation_Binder should not equal Factory binder"
            ),
            array(
                new Horde_Injector_Binder_Implementation('foobar', $df),
                new Horde_Injector_Binder_Implementation('foobar', $df),
                true, "Implementation Binders both reference concrete class foobar"
            ),
            array(
                new Horde_Injector_Binder_Implementation('foobar', $df),
                new Horde_Injector_Binder_Implementation('otherimpl', $df),
                false, "Implementation Binders do not have same implementation set"
            ),
            array(
                new Horde_Injector_Binder_Factory('factory', 'method'),
                new Horde_Injector_Binder_Implementation('foobar', $df),
                false, "Implementation_Binder should not equal Factory binder"
            ),
            array(
                new Horde_Injector_Binder_Factory('foobar', 'create'),
                new Horde_Injector_Binder_Factory('foobar', 'create'),
                true, "Factory Binders both reference factory class foobar::create"
            ),
            array(
                new Horde_Injector_Binder_Factory('foobar', 'create'),
                new Horde_Injector_Binder_Factory('otherimpl', 'create'),
                false, "Factory Binders do not have same factory class set, so they should not be equal"
            ),
            array(
                new Horde_Injector_Binder_Factory('foobar', 'create'),
                new Horde_Injector_Binder_Factory('foobar', 'otherMethod'),
                false, "Factory Binders are set to the same class but different methods. They should not be equal"
            ),
        );
    }

    /**
     * @dataProvider binderIsEqualProvider
     */
    public function testBinderEqualFunction($binderA, $binderB, $shouldEqual, $message)
    {
        $this->assertEquals($shouldEqual, $binderA->equals($binderB), $message);
    }
}
