<?php
/**
 * Demonstrates how to use the default implementation binder with Horde_Injector.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Injector
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @link     http://pear.horde.org/index.php?package=Injector
 */

require 'Horde/Autoloader.php';

interface Person
{
    public function __toString();
}

class World implements Person
{
    public function __toString()
    {
        return 'World';
    }
}

interface Greeter
{
    public function greet();
}

class Hello implements Greeter
{
    public function __construct(Person $somebody)
    {
        $this->somebody = $somebody;
    }

    public function greet()
    {
        print 'Hello ' . $this->somebody;
    }
}

$a = new Horde_Injector(new Horde_Injector_TopLevel());
$a->bindImplementation('Person', 'World');
$a->bindImplementation('Greeter', 'Hello');
$a->getInstance('Greeter')->greet();