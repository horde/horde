<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @category Horde
 * @package  Log
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 */

/**
 * @category Horde
 * @package  Log
 */
class Horde_Log
{
    /** Emergency: system is unusable */
    const EMERG = 0;

    /** Emergency: system is unusable */
    const EMERGENCY = 0;

    /** Alert: action must be taken immediately */
    const ALERT = 1;

    /** Critical: critical conditions */
    const CRIT = 2;

    /** Critical: critical conditions */
    const CRITICAL = 2;

    /** Error: error conditions */
    const ERR = 3;

    /** Error: error conditions */
    const ERROR = 3;

    /** Warning: warning conditions */
    const WARN = 4;

    /** Warning: warning conditions */
    const WARNING = 4;

    /** Notice: normal but significant condition */
    const NOTICE = 5;

    /** Informational: informational messages */
    const INFO = 6;

    /** Informational: informational messages */
    const INFORMATION = 6;

    /** Informational: informational messages */
    const INFORMATIONAL = 6;

    /** Debug: debug-level messages */
    const DEBUG = 7;
}
