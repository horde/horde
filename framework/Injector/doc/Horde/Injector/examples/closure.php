<?php
/**
 * Demonstrates how to use the closure binder with Horde_Injector.
 *
 * PHP version 5.3+
 *
 * @category Horde
 * @package  Injector
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @link     http://pear.horde.org/index.php?package=Injector
 */
require 'Horde/Autoloader.php';

class ClosureCreated
{
    public function __construct($msg)
    {
        $this->msg = $msg;
    }
    public function __toString()
    {
        return 'Foo: ' . $this->msg;
    }
}

$closure = function(Horde_Injector $i) {
    return new ClosureCreated('created by closure');
};
$binder = new Horde_Injector_Binder_Closure($closure);

$a = new Horde_Injector(new Horde_Injector_TopLevel());
$a->bindClosure('CC', $closure);

$b = $a->getInstance('CC');
echo "$b\n";
