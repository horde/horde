<?php
/**
 * @category   Horde
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Support
 */

/**
 * Class for generating a random ID string. This string uses all characters
 * in the class [0-9a-z].
 *
 * <code>
 *  <?php
 *
 *  $rid = (string)new Horde_Support_Randomid([$length = 16]);
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
     *
     * @param integer $length  The length of the ID.
     */
    public function __construct($length = 16)
    {
        $this->generate($length);
    }

    /**
     * Generate a random ID.
     *
     * @param integer $length  The length of the ID.
     */
    public function generate($length = 16)
    {
        $this->_rid = substr(base_convert(dechex(strtr(microtime(), array('0.' => '', ' ' => ''))) . strtr(uniqid(mt_rand(), true), array('.' => '')), 16, 36), 0, $length);
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
