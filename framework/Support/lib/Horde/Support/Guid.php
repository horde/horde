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
 *  $uid = (string)new Horde_Support_Guid;
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
     * Generated GUID
     * @var string
     */
    private $_guid;

    /**
     * New GUID
     */
    public function __construct()
    {
        $this->generate();
    }

    /**
     * Generates a GUID.
     *
     * @return string
     */
    public function generate()
    {
        $this->_guid = date('YmdHis')
            . '.'
            . substr(str_pad(base_convert(microtime(), 10, 36), 16, uniqid(mt_rand()), STR_PAD_LEFT), -16)
            . '@'
            . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
    }

    /**
     * Cooerce to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_guid;
    }

}
