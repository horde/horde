<?php
/**
 * @category   Horde
 * @package    Support
 * @copyright  2009-2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * Class for generating GUIDs. Usage:
 *
 * <code>
 *  <?php
 *
 *  $uid = (string)new Horde_Support_Guid([$opts = array()]);
 *
 *  ?>
 * </code>
 *
 * @category   Horde
 * @package    Support
 * @copyright  2009-2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Horde_Support_Guid
{
    /**
     * Generated GUID.
     *
     * @var string
     */
    private $_guid;

    /**
     * New GUID.
     *
     * @param array $opts  Additional options:
     * <pre>
     * 'prefix' - (string) A prefix to add between the date string and the
     *            random string.
     *            DEFAULT: NONE
     * 'rlength' - (integer) The length of the random string.
     *             DEFAULT: 16
     * 'server' - (string) The server name.
     *            DEFAULT: $_SERVER['SERVER_NAME'] (or 'localhost')
     * </pre>
     */
    public function __construct(array $opts = array())
    {
        $this->generate($opts);
    }

    /**
     * Generates a GUID.
     *
     * @param array $opts  Additional options:
     * <pre>
     * 'prefix' - (string) A prefix to add between the date string and the
     *            random string.
     *            DEFAULT: NONE
     * 'rlength' - (integer) The length of the random string.
     *             DEFAULT: 16
     * 'server' - (string) The server name.
     *            DEFAULT: $_SERVER['SERVER_NAME'] (or 'localhost')
     * </pre>
     */
    public function generate(array $opts = array())
    {
        $this->_guid = date('YmdHis')
            . '.'
            . (isset($opts['prefix']) ? $opts['prefix'] . '.' : '')
            . strval(new Horde_Support_Randomid(isset($opts['rlength']) ? $opts['rlength'] : 16))
            . '@'
            . (isset($opts['server']) ? $opts['server'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost'));
    }

    /**
     * Cooerce to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_guid;
    }

}
