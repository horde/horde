<?php
/**
 * The default notepads handler.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Mnemo
 * @author   Jon Parise <jon@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/apache
 * @link     http://www.horde.org/apps/mnemo
 */

/**
 * The default notepads handler.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category Horde
 * @package  Mnemo
 * @author   Jon Parise <jon@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/apache
 * @link     http://www.horde.org/apps/mnemo
 */
class Mnemo_Notepads_Default
extends Mnemo_Notepads_Base
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
            throw new Mnemo_Exception('This notepad handler needs an "identity" parameter!');
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
        return sprintf(_("Notepad of %s"), $this->_identity->getName());
    }
}