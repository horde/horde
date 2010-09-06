<?php
/**
 * @category   Horde
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Support
 */

/**
 * Class for generating a 24-character random ID string. This string uses all
 * characters in the class [-_0-9a-zA-Z].
 *
 * <code>
 *  <?php
 *
 *  $rid = (string)new Horde_Support_Randomid();
 *
 *  ?>
 * </code>
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Support
 */
class Horde_Support_Randomid
{
    /**
     * Generated ID.
     *
     * @var string
     */
    private $_rid;

    /**
     * New random ID.
     */
    public function __construct()
    {
        $this->generate();
    }

    /**
     * Generate a random ID.
     */
    public function generate()
    {
        // Base64 can have /, +, and = characters. Restrict to URL-safe
        // characters.
        $this->_rid = str_replace(array('/', '+', '='), array('-', '_', ''), base64_encode(pack('I', mt_rand()) . pack('H*', str_replace('.', '', uniqid('', true)) . dechex(getmypid()))));
    }

    /**
     * Cooerce to string.
     *
     * @return string  The random ID.
     */
    public function __toString()
    {
        return $this->_rid;
    }

}
