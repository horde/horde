<?php
/**
 * Demonstrates how to use the annotated setters binder with Horde_Injector.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Injector
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @link     http://pear.horde.org/index.php?package=Injector
 */

require 'Horde/Autoloader.php';

class Worker
{
    public $helper;

    /**
     * @inject
     */
    public function setHelper(Helper $h)
    {
        $this->helper = $h;
    }
}

class Helper
{
    public function __toString()
    {
        return 'helper';
    }
}

$a = new Horde_Injector(new Horde_Injector_TopLevel());
$b = $a->getInstance('Worker');
echo "$b->helper\n";
