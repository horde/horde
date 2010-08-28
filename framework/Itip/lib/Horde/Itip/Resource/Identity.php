<?php
/**
 * Horde_Prefs_Identity based information provider for an invited resource.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Horde_Prefs_Identity based information provider for an invited resource.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * {@link http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL}.
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */
class Horde_Itip_Resource_Identity
implements Horde_Itip_Resource
{
    /**
     * The identity.
     *
     * @var IMP_Prefs_Identity
     */
    private $_identity;

    /**
     * The selected identity for replying.
     *
     * @var string
     */
    private $_reply_to;

    /**
     * Constructor.
     *
     * @param IMP_Prefs_Identity $identity  The IMP identity of the invited
     *                                      resource.
     * @param array              $attendees The attendees of the invitation.
     * @param string             $reply_to  The selected identity for sending the
     *                                      reply.
     * @todo Parse mailto using parse_url
     */
    public function __construct($identity, $attendees, $reply_to)
    {
        $this->_identity = $identity;
        if (!is_array($attendees)) {
            $attendees = array($attendees);
        }
        foreach ($attendees as $attendee) {
            $attendee = preg_replace('/mailto:/i', '', $attendee);
            if (!is_null($id = $identity->getMatchingIdentity($attendee))) {
                $identity->setDefault($id);
                break;
            }
        }
        $this->_reply_to = $reply_to;
    }

    /**
     * Retrieve the mail address of the resource.
     *
     * @return string The mail address.
     */
    public function getMailAddress()
    {
        return $this->_identity->getFromAddress();
    }

    /**
     * Retrieve the reply-to address for the resource.
     *
     * @return string The reply-to address.
     */
    public function getReplyTo()
    {
        $original = $this->_identity->getDefault();
        $this->_identity->setDefault($this->_reply_to);
        $reply_to = $this->_identity->getValue('replyto_addr');
        $this->_identity->setDefault($original);
        return $reply_to;
    }

    /**
     * Retrieve the common name of the resource.
     *
     * @return string The common name.
     */
    public function getCommonName()
    {
        return $this->_identity->getValue('fullname');
    }

    /**
     * Retrieve the "From" address for this resource.
     *
     * @return string The "From" address.
     */
    public function getFrom()
    {
        $cn = $this->getCommonName();
        if (!empty($cn)) {
            return sprintf("%s <%s>", $cn, $this->getMailAddress());
        } else {
            return $this->getMailAddress();
        }
    }
}