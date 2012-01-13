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
     * Reference links.
     *
     * @var array
     */
    private $_references = array();

    /**
     * Tags for the push.
     *
     * @var array
     */
    private $_tags = array();

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
     * Return the content at the given index as a string.
     *
     * @param int $index Index of the content part.
     *
     * @return string The content.
     */
    public function getStringContent($index)
    {
        if (is_resource($this->_content[$index]['content'])) {
            rewind($this->_content[$index]['content']);
            return stream_get_contents($this->_content[$index]['content']);
        } else {
            return $this->_content[$index]['content'];
        }
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
    public function addContent($content, $mime_type = 'text/plain',
                               $params = array())
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
    public function addRecipient(Horde_Push_Recipient $recipient)
    {
        $this->_recipients[] = $recipient;
        return $this;
    }

    /**
     * Add a URL reference for this element.
     *
     * @param string $reference The link.
     *
     * @return Horde_Push This content element.
     */
    public function addReference($reference)
    {
        $this->_references[] = $reference;
        return $this;
    }

    /**
     * Retrieve the URL references for this element.
     *
     * @return array The URL references.
     */
    public function getReferences()
    {
        return $this->_references;
    }

    /**
     * Indicate if this element has URL references.
     *
     * @return boolean True, if there have been links added to the element.
     */
    public function hasReferences()
    {
        return !empty($this->_references);
    }

    /**
     * Add a tag for this element.
     *
     * @param string $tag The tag.
     *
     * @return Horde_Push This content element.
     */
    public function addTag($tag)
    {
        $this->_tags[] = $tag;
        return $this;
    }

    /**
     * Retrieve the tags for this element.
     *
     * @return array The tags.
     */
    public function getTags()
    {
        return $this->_tags;
    }

    /**
     * Indicate if this element has tags.
     *
     * @return boolean True, if there have been tags added to the element.
     */
    public function hasTags()
    {
        return !empty($this->_tags);
    }

    /**
     * Push the content to the recipients.
     *
     * @param array $options Additional options.
     *
     * @return Horde_Push This content element.
     */
    public function push($options = array())
    {
        $results = array();
        foreach ($this->_recipients as $recipient) {
            $results[] = $recipient->push($this, $options);
        }
        return $results;
    }
}
