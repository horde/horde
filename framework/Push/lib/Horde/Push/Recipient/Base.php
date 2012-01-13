<?php
/**
 * The base recipient implementation.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Push
 */

/**
 * The base recipient implementation.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Push
 */
abstract class Horde_Push_Recipient_Base
implements Horde_Push_Recipient
{
    /**
     * The ACL for this recipient.
     *
     * @var string
     */
    private $_acl;

    /**
     * Set the ACL for this recipient.
     *
     * @param string $acl The ACL.
     *
     * @return NULL
     */
    public function setAcl($acl)
    {
        $this->_acl = $acl;
    }

    /**
     * Retrieve the ACL setting for this recipient.
     *
     * @return string The ACL.
     */
    protected function getAcl()
    {
        return $this->_acl;
    }
}
