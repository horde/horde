<?php
/**
 * Demonstrates how to use the default factory binder with Horde_Injector.
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

class Greet
{
    public function __construct($somebody)
    {
        $this->somebody = $somebody;
    }

    public function greet()
    {
        print 'Hello ' . $this->somebody;
    }
}

class Factory
{
    static public function getGreeter(Horde_Injector $injector)
    {
        return new Greet($injector->getInstance('Person'));
    }
}

$a = new Horde_Injector(new Horde_Injector_TopLevel());
$a->setInstance('Person', 'Bob');
$a->bindFactory('Greet', 'Factory', 'getGreeter');
$a->getInstance('Greet')->greet();