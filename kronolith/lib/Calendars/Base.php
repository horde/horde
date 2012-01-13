<?php
/**
 * The base functionality of the calendars handler.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kronolith
 */
abstract class Kronolith_Calendars_Base
{
    /**
     * The share backend.
     *
     * @var Horde_Share_Base
     */
    protected $shares;

    /**
     * The current user.
     *
     * @var string
     */
    protected $user;

    /**
     * Additional parameters for the tasklist handling.
     *
     * @var array
     */
    protected $params;

    /**
     * Constructor.
     *
     * @param Horde_Share_Base $shares The share backend.
     * @param string           $user   The current user.
     * @param array            $params Additional parameters.
     */
    public function __construct($shares, $user, $params)
    {
        $this->shares = $shares;
        $this->user = $user;
        $this->params = $params;
    }

    /**
     * Create the default calendar share for the current user.
     *
     * @return Horde_Share_Object The new default share.
     */
    public function createDefaultShare()
    {
        $share = $this->shares->newShare(
            $this->user,
            strval(new Horde_Support_Randomid()),
            $this->getDefaultShareName()
        );
        $this->shares->addShare($share);
        return $share;
    }

    /**
     * Return the name of the default share.
     *
     * @return string The name of a default share.
     */
    abstract protected function getDefaultShareName();
}