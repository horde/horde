<?php
/**
 * Blogger.com as recipient.
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
 * Blogger.com as recipient.
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
class Horde_Push_Recipient_Blogger
extends Horde_Push_Recipient_Base
{
    /**
     * The HTTP client.
     *
     * @var Horde_Http_Client
     */
    private $_client;

    /**
     * The connection details for blogger.com
     *
     * @var array
     */
    private $_params;

    /**
     * Constructor.
     *
     * @param Horde_Http_Client $client The HTTP handler for connecting to 
     *                                  blogger.com.
     * @param array $params             The connection details for blogger.com.
     */
    public function __construct($client, $params)
    {
        $this->_client = $client;
        $this->_params = $params;
    }

    /**
     * Push content to the recipient.
     *
     * @param Horde_Push $content The content element.
     * @param array      $options Additional options.
     *
     * @return NULL
     */
    public function push(Horde_Push $content, $options = array())
    {
        $entry = new Horde_Feed_Entry_Atom(null, $this->_client);

        $types = $content->getMimeTypes();
        if (isset($types['text/html'])) {
            $body = $content->getStringContent($types['text/html'][0]);
        } else if (isset($types['text/plain'])) {
            $body = $content->getStringContent($types['text/plain'][0]);
        } else {
            $body = '';
        }

        /* Give the entry its initial values. */
        $entry->{'atom:title'} = $content->getSummary();
        $entry->{'atom:title'}['type'] = 'text';
        $entry->{'atom:content'} = $body;
        $entry->{'atom:content'}['type'] = 'text';
        
        if (!empty($options['pretend'])) {
            return sprintf(
                "Would push \n\n%s\n\n to %s.",
                (string) $entry,
                $this->_params['url']
            );
        }

        /* Authenticate. */
        $response = $this->_client->post(
            'https://www.google.com/accounts/ClientLogin',
            'accountType=GOOGLE&service=blogger&source=horde-push&Email=' . $this->_params['username'] . '&Passwd=' . $this->_params['password'],
            array('Content-type', 'application/x-www-form-urlencoded')
        );
        if ($response->code !== 200) {
            throw new Horde_Push_Exception('Expected response code 200, got ' . $response->code);
        }

        $auth = null;
        foreach (explode("\n", $response->getBody()) as $line) {
            $param = explode('=', $line);
            if ($param[0] == 'Auth') {
                $auth = $param[1];
            }
        }
        if (empty($auth)) {
            throw new Horde_Push_Exception(
                'Missing authentication token in the response!'
            );
        }
        
        /* Do the initial post. */
        try {
            $entry->save(
                $this->_params['url'],
                array('Authorization' => 'GoogleLogin auth=' . $auth)
            );
            $reference = $entry->link('alternate');
            if (!empty($reference)) {
                $content->addReference($reference);
            }
        } catch (Horde_Exception $e) {
            throw new Horde_Push_Exception($e);
        }

        return sprintf(
            'Pushed blog entry to %s.', $this->_params['url']
        );
    }
}
