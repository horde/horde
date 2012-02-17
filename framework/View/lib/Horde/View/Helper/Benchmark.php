<?php
/**
 * Measures the execution time of a block in a template and reports the result
 * to the log.
 *
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */

/**
 * Measures the execution time of a block in a template and reports the result
 * to the log.
 *
 * Example:
 *
 * <code>
 * <?php $bench = $this->benchmark('Notes section') ?>
 * <?php echo $this->expensiveNotesOperation() ?>
 * <?php $bench->end() ?>
 * </code>
 *
 * Will add something like "Notes section (0.34523)" to the log.
 *
 * You may give an optional logger level as the second argument ('debug',
 * 'info', 'warn', 'error').  The default is 'info'.  The level may also be
 * given as a Horde_Log::* constant.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */
class Horde_View_Helper_Benchmark extends Horde_View_Helper_Base
{
    /**
     * Starts a new benchmark.
     *
     * @param string $message        Message to log after the benchmark has
     *                               ended.
     * @param string|integer $level  Log level to log after the benchmark has
     *                               ended.
     *
     * @return Horde_View_Helper_Benchmark_Timer  A benchmark timer object.
     */
    public function benchmark($message = 'Benchmarking', $level = 'info')
    {
        return new Horde_View_Helper_Benchmark_Timer($message, $level,
                                                     $this->_view->logger);
    }
}
