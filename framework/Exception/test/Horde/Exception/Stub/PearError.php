<?php
/**
 * Stub replacement for PEAR Errors
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Exception
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Exception
 */
class Horde_Exception_Stub_PearError
{
    private $_message;
    private $_code;

    public function __construct($message = null, $code = null)
    {
        $this->_message = $message;
        $this->_code    = $code;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function getCode()
    {
        return $this->_code;
    }
}