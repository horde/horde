<?php
/**
 * Basic iTip response type definition.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Basic iTip response type definition.
 *
 * Copyright 2010 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * {@link http://www.horde.org/licenses/lgpl21 LGPL}.
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
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
     * The invited resource.
     *
     * @var Horde_Itip_Resource
     */
    private $_resource;

    /**
     * An optional comment that should appear in the response subject.
     *
     * @var string
     */
    private $_comment;

    /**
     * Constructor.
     *
     * @param Horde_Itip_Resource $resource  The invited resource. 
     * @param string              $comment   A comment for the subject line.
     */
    public function __construct(
        Horde_Itip_Resource $resource,
        $comment = null
    )
    {
        $this->_resource = $resource;
        $this->_comment  = $comment;
    }

    /**
     * Set the request.
     *
     * @param Horde_Itip_Event $request The request this instance will respond
     *                                  to.
     *
     * @return NULL
     */
    public function setRequest(
        Horde_Itip_Event $request
    )
    {
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
     * Return the subject of the response without using the comment.
     *
     * @return string The subject.
     */
    public function getBriefSubject()
    {
        return sprintf(
            '%s: %s',
            $this->getShortSubject(),
            $this->getRequest()->getSummary()
        );
    }
    /**
     * Return the subject of the response.
     *
     * @return string The subject.
     */
    public function getSubject()
    {
        if ($this->_comment === null) {
            return $this->getBriefSubject();
        } else {
            return sprintf(
                '%s [%s]: %s',
                $this->getShortSubject(),
                $this->_comment,
                $this->getRequest()->getSummary()
            );
        }
    }

    /**
     * Return an additional message for the response.
     *
     * @param boolean $is_update Indicates if the request was an update.
     *
     * @return string The message.
     */
    public function getMessage($is_update = false)
    {
        if ($this->_comment === null) {
            return sprintf(
                "%s %s:\n\n%s",
                $this->_resource->getCommonName(),
                $this->getShortMessage($is_update),
                $this->getRequest()->getSummary()
            );
        } else {
            return sprintf(
                "%s %s:\n\n%s\n\n%s",
                $this->_resource->getCommonName(),
                $this->getShortMessage($is_update),
                $this->getRequest()->getSummary(),
                $this->_comment
            );
        }
    }
}