<?php
/**
 * A content element that will be pushed to various recipients.
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
 * A content element that will be pushed to various recipients.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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
class Horde_Push
{
    /**
     * Content summary.
     *
     * @var string
     */
    private $_summary = '';

    /**
     * Content.
     *
     * @var array
     */
    private $_content = array();

    /**
     * Content types.
     *
     * @var array
     */
    private $_types = array();

    /**
     * The recipients that will receive the content.
     *
     * @var array
     */
    private $_recipients = array();

    /**
     * Return the summary for this content element.
     *
     * @return string The summary.
     */
    public function getSummary()
    {
        return $this->_summary;
    }

    /**
     * Set the summary for this content element.
     *
     * @param string $summary The summary.
     *
     * @return Horde_Push This content element.
     */
    public function setSummary($summary)
    {
        $this->_summary = $summary;
        return $this;
    }

    /**
     * Return the contents for this element.
     *
     * @return array The content list.
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Return the contents by MIME type for this element.
     *
     * @return array The content list ordered by MIME type.
     */
    public function getMimeTypes()
    {
        return $this->_types;
    }

    /**
     * Add content to this element.
     *
     * @param string|resource $content   The UTF-8 encoded content.
     * @param string          $mime_type The MIME type of the content.
     * @param array           $params    Content specific parameters.
     *
     * @return Horde_Push This content element.
     */
    public function addContent(
        $content,
        $mime_type = 'text/plain',
        $params = array()
    )
    {
        $this->_types[$mime_type][] = count($this->_content);
        $this->_content[] = array(
            'content' => $content,
            'mime_type' => $mime_type,
            'params' => $params
        );
        return $this;
    }

    /**
     * Add a recipient for this element.
     *
     * @param Horde_Push_Recipient $recipient The recipient.
     *
     * @return Horde_Push This content element.
     */
    public function addRecipient(
        Horde_Push_Recipient $recipient
    )
    {
        $this->_recipients[] = $recipient;
        return $this;
    }

    /**
     * Push the content to the recipients.
     *
     * @return Horde_Push This content element.
     */
    public function push()
    {
        foreach ($this->_recipients as $recipient) {
            $recipient->push($this);
        }
        return $this;
    }
}
