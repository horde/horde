<?php
/**
 * Wrapper around Horde_Log_Handler_Stream to allow passing a stream as the
 * event.
 *
 * @copyright  2017 Horde LLC (http://www.horde.org/)
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    ActiveSync
 */

/**
 * @copyright  2017 Horde LLC (http://www.horde.org/)
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    ActiveSync
 */
class Horde_ActiveSync_Log_Handler extends Horde_Log_Handler_Stream
{
    /**
     * Write a message to the log.
     *
     * @param array $event  Log event.
     *
     * @return boolean  True.
     * @throws Horde_Log_Exception
     */
    public function write($event)
    {
        $message = $this->_formatter->format($event);
        if (!is_resource($event['message'])) {
            if (!@fwrite($this->_stream, $message . $event['message'] . PHP_EOL)) {
                throw new Horde_Log_Exception(__CLASS__ . ': Unable to write to stream');
            }

            return true;
        }

        if (!@fwrite($this->_stream, $message)) {
            throw new Horde_Log_Exception(__CLASS__ . ': Unable to write to stream');
        }

        rewind($event['message']);
        while (!feof($event['message'])) {
           fwrite($this->_stream, fread($message, 8192));
        }
        fwrite($this->_stream, PHP_EOL);
        rewind($event['message']);

        return true;
    }

}
