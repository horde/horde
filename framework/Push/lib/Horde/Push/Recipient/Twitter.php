<?php
/**
 * Twitter as recipient.
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
 * Twitter as recipient.
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
class Horde_Push_Recipient_Twitter
extends Horde_Push_Recipient_Base
{
    /**
     * The twitter client.
     *
     * @var Horde_Service_Twitter
     */
    private $_twitter;

    /**
     * A HTTP client.
     *
     * @var Horde_Http_Client
     */
    private $_client;

    /**
     * Constructor.
     *
     * @param Horde_Service_Twitter $twitter The twitter client.
     */
    public function __construct(Horde_Service_Twitter $twitter,
                                $client = null)
    {
        $this->_twitter = $twitter;
        $this->_client = $client;
    }

    /**
     * Push content to the recipient.
     *
     * @param Horde_Push $content The content element.
     * @param array      $options Additional options.
     *
     * @return string The result description.
     */
    public function push(Horde_Push $content, $options = array())
    {
        $tweet = $content->getSummary();
        if ($content->hasReferences() && strlen($tweet) < 116 &&
            class_exists('Horde_Service_UrlShortener_Base') &&
            $this->_client !== null) {
            $shortener = new Horde_Service_UrlShortener_TinyUrl($this->_client);
            foreach ($content->getReferences() as $reference) {
                $tweet .= ' ' . $shortener->shorten($reference);
                if (strlen($tweet) > 115) {
                    break;
                }
            }
        }
        if ($content->hasTags()) {
            foreach ($content->getTags() as $tag) {
                if (strlen($tweet) + strlen($tag) < 139) {
                    $tweet .= ' #' . $tag;
                }
            }
        }
        if (empty($options['pretend'])) {
            $this->_twitter->statuses->update($tweet);
            return 'Pushed tweet to twitter.';
        } else {
            return sprintf(
                'Would push tweet "%s" to twitter.', $tweet
            );
        }
    }
}
