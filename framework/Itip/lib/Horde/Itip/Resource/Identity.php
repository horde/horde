<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://www.horde.org/licenses/lgpl21 LGPL.
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Horde_Prefs_Identity based information provider for an invited resource.
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */
class Horde_Itip_Resource_Identity implements Horde_Itip_Resource
{
    /**
     * The identity.
     *
     * @var Horde_Prefs_Identity
     */
    protected $_identity;

    /**
     * The selected identity for replying.
     *
     * @var string
     */
    protected $_reply_to;

    /**
     * Constructor.
     *
     * @param Horde_Prefs_Identity $identity  The identity of the invited
     *                                        resource.
     * @param array $attendees                The attendees of the invitation.
     * @param string $reply_to                The selected identity for sending
     *                                        the reply.
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
     * Retrieve the bare email address of the resource. I.e., addr-spec.
     *
     * @return string The mail address.
     */
    public function getMailAddress()
    {
        return $this->_identity->getFromAddress()->bare_address;
    }

    /**
     * Retrieve the reply-to address for the resource.
     *
     * @return string The reply-to address.
     */
    public function getReplyTo()
    {
        return $this->_identity->getValue('replyto_addr', $this->_reply_to);
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
        return (string)$this->_identity->getDefaultFromAddress(true);
    }
}