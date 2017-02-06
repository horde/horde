<?php
/**
 * Copyright 2006-2017 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd
 * @category Horde
 * @package  Db
 */

/**
 * Encapsulation object for LOB values to be used in SQL statements to ensure
 * proper quoting, escaping, retrieval, etc.
 *
 * @property $value  The binary value as a string. @since Horde_Db 2.1.0
 * @property $stream  The binary value as a stream. @since Horde_Db 2.4.0
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd
 * @category Horde
 * @package  Db
 */
abstract class Horde_Db_Value_Lob implements Horde_Db_Value
{
    /**
     * Binary scalar value to be quoted
     *
     * @var string
     */
    protected $_value;

    /**
     * Binary stream value to be quoted
     *
     * @var stream
     */
    protected $_stream;

    /**
     * Constructor
     *
     * @param string|stream $binaryValue  The binary value in a string or
     *                                    stream resource.
     */
    public function __construct($binaryValue)
    {
        if (is_resource($binaryValue)) {
            $this->stream = $binaryValue;
        } else {
            $this->value = $binaryValue;
        }
    }

    /**
     * Getter for $value and $stream properties.
     */
    public function __get($name)
    {
        switch ($name) {
        case 'value':
            if (isset($this->_value)) {
                return $this->_value;
            }
            if (isset($this->_stream)) {
                rewind($this->_stream);
                return stream_get_contents($this->_stream);
            }
            break;

        case 'stream':
            if (isset($this->_stream)) {
                return $this->_stream;
            }
            if (isset($this->_value)) {
                $stream = @fopen('php://temp', 'r+');
                fwrite($stream, $this->_value);
                rewind($stream);
                return $stream;
            }
            break;
        }
    }

    /**
     * Setter for $value and $stream properties.
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'value':
        case 'stream':
            $this->{'_' . $name} = $value;
            break;
        }
    }
}
