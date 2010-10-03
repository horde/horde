<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
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
     * The migration version
     * @var integer
     */
    public $version = null;

    /**
     * The logger
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Database connection adapter
     * @var Horde_Db_Adapter_Base
     */
    protected $_connection;


    /*##########################################################################
    # Constructor
    ##########################################################################*/

    /**
     */
    public function __construct(Horde_Db_Adapter_Base $connection, $version = null)
    {
        $this->_connection = $connection;
        $this->version = $version;
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
        $a = array();
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
            $this->log();
        }
        if ($direction == 'down') {
            $this->announce("reverted (" . sprintf("%.4fs", $time) . ")");
            $this->log();
        }
        return $result;
    }

    /**
     * @param   string  $text
     */
    public function log($text = '')
    {
        if ($this->_logger) {
            $this->_logger->info($text);
        }
    }

    /**
     * @param Horde_Log_Logger $logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Announce migration
     * @param   string  $message
     */
    public function announce($message)
    {
        $text = "$this->version " . get_class($this) . ": $message";
        $length = 75 - strlen($text) > 0 ? 75 - strlen($text) : 0;

        $this->log(sprintf("== %s %s", $text, str_repeat('=', $length)));
    }

    /**
     * @param   string  $message
     * @param   boolean $subitem
     */
    public function say($message, $subitem = false)
    {
        $this->log(($subitem ? "   ->" : "--") . " $message");
    }
}
