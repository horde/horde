<?php
/**
 * Demonstrates how to use the closure binder with Horde_Injector.
 *
 * PHP version 5.3+
 *
 * @category Horde
 * @package  Injector
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @link     http://pear.horde.org/index.php?package=Injector
 */

if (version_compare(PHP_VERSION, '5.3', 'lt')) {
    echo "PHP 5.3+ is required for the closure binder\n";
    exit(1);
}

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
