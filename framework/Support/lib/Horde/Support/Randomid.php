<?php
/**
 * @category   Horde
 * @copyright  2010-2011 Horde LLC (http://www.horde.org/)
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Support
 */

/**
 * Class for generating a 23-character random ID string. This string uses all
 * characters in the class [-_0-9a-zA-Z].
 *
 * <code>
 * $id = (string)new Horde_Support_Randomid();
 * </code>
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2010-2011 Horde LLC (http://www.horde.org/)
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Support
 */
class Horde_Support_Randomid
{
    /**
     * Generated ID.
     *
     * @var string
     */
    private $_id;

    /**
     * New random ID.
     */
    public function __construct()
    {
        $this->_id = $this->generate();
    }

    /**
     * Generate a random ID.
     */
    public function generate()
    {
        $pid = function_exists('zend_thread_id')
            ? zend_thread_id()
            : getmypid();

        /* Base64 can have /, +, and = characters. Restrict to URL-safe
         * characters. */
        return str_replace(
            array('/', '+', '='),
            array('-', '_', ''),
            base64_encode(
                pack('II', mt_rand(), crc32(php_uname('n')))
                . pack('H*', uniqid() . sprintf('%04s', dechex($pid)))));
    }

    /**
     * Cooerce to string.
     *
     * @return string  The random ID.
     */
    public function __toString()
    {
        return $this->_id;
    }
}
