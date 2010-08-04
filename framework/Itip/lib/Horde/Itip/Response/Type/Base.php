<?php
/**
 * Basic iTip response type definition.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Basic iTip response type definition.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
abstract class Horde_Itip_Response_Type_Base
implements Horde_Itip_Response_Type
{
    /**
     * The request we are going to answer.
     *
     * @var Horde_Itip_Event
     */
    private $_request;

    /**
     * Set the request.
     *
     * @param Horde_Itip_Event $request  The request this
     *                                                  instance will respond
     *                                                    to.
     *
     * @return NULL
     */
    public function setRequest(
        Horde_Itip_Event $request
    ) {
        $this->_request  = $request;
    }

    /**
     * Get the request for this response.
     *
     * @return Horde_Itip_Event The request this instance will
     *                                         respond to.
     *
     * @throws Horde_Itip_Exception If the request has not been
     *                                             set yet.
     */
    public function getRequest()
    {
        if (empty($this->_request)) {
            throw new Horde_Itip_Exception(
                'The iTip request is still undefined!'
            );
        }
        return $this->_request;
    }

    /**
     * Return the subject of the response.
     *
     * @param string $comment An optional comment that should appear in the
     *                        response subject.
     *
     * @return string The subject.
     */
    public function getSubject($comment = null)
    {
        if ($comment === null) {
            return sprintf(
                '%s: %s',
                $this->getShortSubject(),
                $this->getRequest()->getSummary()
            );
        } else {
            return sprintf(
                '%s [%s]: %s',
                $this->getShortSubject(),
                $comment,
                $this->getRequest()->getSummary()
            );
        }
    }

    /**
     * Return an additional message for the response.
     *
     * @param boolean $is_update Indicates if the request was an update.
     * @param string  $comment   An optional comment that should appear in the
     *                           response message.
     *
     * @return string The message.
     */
    public function getMessage($is_update = false, $comment = null)
    {
        if ($comment === null) {
            return sprintf(
                "%s %s:\n\n%s",
                $this->getShortMessage($update),
                $this->getRequest()->getSummary()
            );
        } else {
            return sprintf(
                "%s %s:\n\n%s\n\n%s",
                $this->getShortMessage($update),
                $this->getRequest()->getSummary(),
                $comment
            );
        }
    }
}