<?php
/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Xml_Element
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Xml_Element
 */
class Horde_Xml_Element_Exception extends Exception
{
    /**
     * Create an exception, filling in some details from the result of
     * error_get_last if they are passed in $code_or_lasterror.
     */
    public function __construct($message = null, $code_or_lasterror = 0)
    {
        if (is_array($code_or_lasterror)) {
            if ($message) {
                $message .= $code_or_lasterror['message'];
            } else {
                $message = $code_or_lasterror['message'];
            }

            $this->file = $code_or_lasterror['file'];
            $this->line = $code_or_lasterror['line'];
            $code = $code_or_lasterror['type'];
        } else {
            $code = $code_or_lasterror;
        }

        parent::__construct($message, $code);
    }

}
