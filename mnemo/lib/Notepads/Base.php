<?php
/**
 * The base functionality of the notepads handler.
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
 * The base functionality of the notepads handler.
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
abstract class Mnemo_Notepads_Base
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
     * Additional parameters for the notepad handling.
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
     * Ensure the share system has a default notepad share for the current user
     * if the default share feature is activated.
     *
     * @return string|NULL The id of the new default share or NULL if no share
     *                     was created.
     */
    public function ensureDefaultShare()
    {
        /* If the user doesn't own a task list, create one. */
        if (!empty($this->params['auto_create']) && $this->user &&
            !count(Mnemo::listNotepads(true))) {
            $share = $this->shares->newShare(
                $this->user,
                strval(new Horde_Support_Randomid()),
                $this->getDefaultShareName()
            );
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
}