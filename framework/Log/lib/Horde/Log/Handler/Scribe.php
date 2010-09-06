<?php
/**
 * Horde Log package
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage Handlers
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage Handlers
 */
class Horde_Log_Handler_Scribe extends Horde_Log_Handler_Base
{
    /**
     * Scribe client.
     *
     * @var Horde_Scribe_Client
     */
    protected $_scribe;

    /**
     * Formats the log message before writing.
     *
     * @var Horde_Log_Formatter
     */
    protected $_formatter;

    /**
     * Options to be set by setOption().
     *
     * @var array
     */
    protected $_options = array(
        'addNewline' => false,
        'category'   => 'default'
    );

    /**
     * Constructor.
     *
     * @param Horde_Scribe_Client $scribe     Scribe client.
     * @param Horde_Log_Formatter $formatter  Log formatter.
     */
    public function __construct(Horde_Scribe_Client $scribe,
                                Horde_Log_Formatter $formatter = null)
    {
        $this->_formatter = is_null($formatter)
            ? new Horde_Log_Formatter_Simple()
            : $formatter;
        $this->_scribe = $scribe;
    }

    /**
     * Write a message to the log.
     *
     * @param array $event  Log event.
     *
     * @return boolean  True.
     */
    public function write($event)
    {
        $category = isset($event['category'])
            ? $event['category']
            : $this->_options['category'];

        $message = $this->_formatter->format($event);
        if (!$this->_options['addNewline']) {
            $message = rtrim($message);
        }

        $this->_scribe->log($category, $message);

        return true;
    }

}
