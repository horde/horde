<?php
/**
 * Copyright 2007-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category  Horde
 * @copyright 2007-2014 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Stream_Wrapper
 */

/**
 * A stream wrapper that will treat a native PHP string as a stream.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2007-2014 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Stream_Wrapper
 */
class Horde_Stream_Wrapper_String
{
    /**
     * The current context.
     *
     * @var resource
     */
    public $context;

    /**
     * String position.
     *
     * @var integer
     */
    protected $_pos;

    /**
     * The string.
     *
     * @var string
     */
    protected $_string;

    /**
     * @see streamWrapper::stream_open()
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $options = stream_context_get_options($this->context);
        if (empty($options['horde-string']['string']) || ! $options['horde-string']['string'] instanceof Horde_Stream_Wrapper_StringStream) {
            throw new Exception('String streams must be created using the Horde_Stream_Wrapper_StringStream interface');
        }

        $this->_string =& $options['horde-string']['string']->getString();
        if (is_null($this->_string)) {
            return false;
        }

        $this->_pos= 0;

        return true;
    }

    /**
     * @see streamWrapper::stream_read()
     */
    public function stream_read($count)
    {
        $current = $this->_pos;
        $this->_pos+= $count;
        return substr($this->_string, $current, $count);
    }

    /**
     * @see streamWrapper::stream_write()
     */
    public function stream_write($data)
    {
        return strlen($data);
    }

    /**
     * @see streamWrapper::stream_tell()
     */
    public function stream_tell()
    {
        return $this->_pos;
    }

    /**
     * @see streamWrapper::stream_eof()
     */
    public function stream_eof()
    {
        return ($this->_pos> strlen($this->_string));
    }

    /**
     * @see streamWrapper::stream_stat()
     */
    public function stream_stat()
    {
        return array(
            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => strlen($this->_string),
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => 0,
            'blocks' => 0
        );
    }

    /**
     * @see streamWrapper::stream_seek()
     */
    public function stream_seek($offset, $whence)
    {
        if ($offset > strlen($this->_string)) {
            return false;
        }

        switch ($whence) {
        case SEEK_SET:
            $this->_pos= $offset;
            break;

        case SEEK_CUR:
            $target = $this->_pos+ $offset;
            if ($target < strlen($this->_string)) {
                $this->_pos= $target;
            } else {
                return false;
            }
            break;

        case SEEK_END:
            $this->_pos = strlen($this->_string) - $offset;
            break;
        }

        return true;
    }

}
