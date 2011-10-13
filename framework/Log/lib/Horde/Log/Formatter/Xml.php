<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Formatters
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Formatters
 */
class Horde_Log_Formatter_Xml implements Horde_Log_Formatter
{
    /**
     * Config options.
     *
     * @var array
     */
    protected $_options = array(
        'elementEntry'     => 'log',
        'elementTimestamp' => 'timestamp',
        'elementMessage'   => 'message',
        'elementLevel'     => 'level',
        'lineEnding'       => PHP_EOL
    );

    /**
     * Constructor.
     *
     * TODO
     */
    public function __construct($options = array())
    {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Formats an event to be written by the handler.
     *
     * @param array $event  Log event.
     *
     * @return string  XML string.
     */
    public function format($event)
    {
        $dom = new DOMDocument();

        $elt = $dom->appendChild(new DOMElement($this->_options['elementEntry']));
        $elt->appendChild(new DOMElement($this->_options['elementTimestamp'], date('c')));
        $elt->appendChild(new DOMElement($this->_options['elementMessage'], $event['message']));
        $elt->appendChild(new DOMElement($this->_options['elementLevel'], $event['level']));

        return preg_replace('/<\?xml version="1.0"( encoding="[^\"]*")?\?>\n/u', '', $dom->saveXML()) . $this->_options['lineEnding'];
    }

}
