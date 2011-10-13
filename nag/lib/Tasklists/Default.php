<?php
/**
 * The default tasklists handler.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Nag
 */
class Nag_Tasklists_Default
extends Nag_Tasklists_Base
{
    /**
     * The current identity.
     *
     * @var Horde_Prefs_Identity
     */
    private $_identity;

    /**
     * Constructor.
     *
     * @param Horde_Share_Base $shares The share backend.
     * @param string           $user   The current user.
     * @param array            $params Additional parameters.
     */
    public function __construct($shares, $user, $params)
    {
        if (!isset($params['identity'])) {
            throw new Nag_Exception('This tasklist handler needs an "identity" parameter!');
        } else {
            $this->_identity = $params['identity'];
            unset($params['identity']);
        }
        parent::__construct($shares, $user, $params);
    }

    /**
     * Return the name of the default share.
     *
     * @return string The name of a default share.
     */
    protected function getDefaultShareName()
    {
        return sprintf(_("Task list of %s"), $this->_identity->getName());
    }
}