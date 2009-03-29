<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Migration
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Migration
 */
class Horde_Db_Migration_Base
{
    /**
     * Print messages as migrations happen
     * @var boolean
     */
    public static $verbose = true;

    /**
     * The migration version
     * @var integer
     */
    public $version = null;
    
    /**
     * Database connection adapter
     * @var Horde_Db_Adapter_Abstract
     */
    protected $_connection;


    /*##########################################################################
    # Constructor
    ##########################################################################*/

    /**
     */
    public function __construct($context)
    {
        $this->version = $context['version'];
        $this->_connection = $context['connection'];
    }


    /*##########################################################################
    # Public
    ##########################################################################*/

    /**
     * Proxy methods over to the connection
     * @param   string  $method
     * @param   array   $args
     */
    public function __call($method, $args)
    {
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $vals = array();
                foreach ($arg as $key => $value) {
                    $vals[] = "$key => " . var_export($value, true);
                }
                $a[] = 'array(' . implode(', ', $vals) . ')';
            } else {
                $a[] = $arg;
            }
        }
        $this->say("$method(" . implode(", ", $a) . ")");

        // benchmark method call
        $t = new Horde_Support_Timer();
        $t->push();
            $result = call_user_func_array(array($this->_connection, $method), $args);
        $time = $t->pop();

        // print stats
        $this->say(sprintf("%.4fs", $time), 'subitem');
        if (is_int($result)) {
            $this->say("$result rows", 'subitem');
        }

        return $result;
    }

    public function upWithBechmarks()
    {
        $this->migrate('up');
    }

    public function downWithBenchmarks()
    {
        $this->migrate('down');
    }

    /**
     * Execute this migration in the named direction
     */
    public function migrate($direction)
    {
        if (!method_exists($this, $direction)) { return; }

        if ($direction == 'up')   { $this->announce("migrating"); }
        if ($direction == 'down') { $this->announce("reverting"); }

        $result = null;
        $t = new Horde_Support_Timer();
        $t->push();
            $result = $this->$direction();
        $time = $t->pop();

        if ($direction == 'up') {
            $this->announce("migrated (" . sprintf("%.4fs", $time) . ")");
            $this->write();
        }
        if ($direction == 'down') {
            $this->announce("reverted (" . sprintf("%.4fs", $time) . ")");
            $this->write();
        }
        return $result;
    }

    /**
     * @param   string  $text
     */
    public function write($text = '')
    {
        if (self::$verbose) {
            echo "$text\n";
        }
    }

    /**
     * Announce migration
     * @param   string  $message
     */
    public function announce($message)
    {
        $text = "$this->version " . get_class($this) . ": $message";
        $length = 75 - strlen($text) > 0 ? 75 - strlen($text) : 0;

        $this->write(sprintf("== %s %s", $text, str_repeat('=', $length)));
    }

    /**
     * @param   string  $message
     * @param   boolean $subitem
     */
    public function say($message, $subitem = false)
    {
        $this->write(($subitem ? "   ->" : "--") . " $message");
    }

}
