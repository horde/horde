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
    protected $_shares;

    /**
     * The current user.
     *
     * @var string
     */
    protected $_user;

    /**
     * Additional parameters for the tasklist handling.
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor.
     *
     * @param Horde_Share_Base $shares The share backend.
     * @param string           $user   The current user.
     * @param array            $params Additional parameters.
     */
    public function __construct($shares, $user, $params)
    {
        $this->_shares = $shares;
        $this->_user = $user;
        $this->_params = $params;
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
        if (!empty($this->_params['auto_create']) && $this->_user &&
            !count(Nag::listTasklists(true))) {
            $share = $this->_shares->newShare(
                $this->_user,
                strval(new Horde_Support_Randomid()),
                $this->_getDefaultShareName()
            );
            $share->set('color', Nag::randomColor());
            $this->_prepareDefaultShare($share);
            $this->_shares->addShare($share);
            return $share->getName();
        }
    }

    /**
     * Returns the default share's ID, if it can be determined from the share
     * backend.
     *
     * @return string  The default share ID.
     */
    public function getDefaultShare()
    {
        $shares = $this->_shares->listShares(
            $this->_user,
            array('attributes' => $this->_user)
        );
        foreach ($shares as $id => $share) {
            if ($share->get('default')) {
                return $id;
            }
        }
    }

    /**
     * Runs any actions after setting a new default tasklist.
     *
     * @param string $share  The default share ID.
     */
    public function setDefaultShare($share)
    {
    }

    /**
     * Return the name of the default share.
     *
     * @return string The name of a default share.
     */
    abstract protected function _getDefaultShareName();

    /**
     * Add any modifiers required to the share in order to mark it as default.
     *
     * @param Horde_Share_Object $share The new default share.
     */
    protected function _prepareDefaultShare($share)
    {
    }
}