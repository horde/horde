<?php
/**
 * The base functionality of the tasklists handler.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Nag
 */
abstract class Nag_Tasklists_Base
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
     * Ensure the share system has a default tasklist share for the current user
     * if the default share feature is activated.
     *
     * @return string|NULL The id of the new default share or NULL if no share
     *                     was created.
     */
    public function ensureDefaultShare()
    {
        /* If the user doesn't own a task list, create one. */
        if (!empty($this->params['auto_create']) && $this->user &&
            !count(Nag::listTasklists(true))) {
            $share = $this->shares->newShare(
                $this->user,
                strval(new Horde_Support_Randomid()),
                $this->getDefaultShareName()
            );
            $share->set('color', Nag::randomColor());
            $this->prepareDefaultShare($share);
            $this->shares->addShare($share);
            return $share->getName();
        }
    }

    /**
     * Return the name of the default share.
     *
     * @return string The name of a default share.
     */
    abstract protected function getDefaultShareName();

    /**
     * Add any modifiers required to the share in order to mark it as default.
     *
     * @param Horde_Share_Object $share The new default share.
     */
    protected function prepareDefaultShare($share)
    {
    }
}