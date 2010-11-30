<?php
/**
 * Portions Copyright 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * @category Horde
 * @package  Horde_Feed
 */

/**
 * Concrete class for working with Atom entries.
 *
 * @category Horde
 * @package  Horde_Feed
 */
class Horde_Feed_Entry_Atom extends Horde_Feed_Entry_Base
{
    /**
     * The XML string for an "empty" Atom entry.
     *
     * @var string
     */
    protected $_emptyXml = '<atom:entry/>';

    /**
     * Name of the XML element for Atom entries. Subclasses can
     * override this to something other than "entry" if necessary.
     *
     * @var string
     */
    protected $_entryElementName = 'entry';

    /**
     * Delete an atom entry.
     *
     * Delete tries to delete this entry from its feed. If the entry
     * does not contain a link rel="edit", we throw an error (either
     * the entry does not yet exist or this is not an editable
     * feed). If we have a link rel="edit", we do the empty-body
     * HTTP DELETE to that URI and check for a response of 2xx.
     * Usually the response would be 204 No Content, but the Atom
     * Publishing Protocol permits it to be 200 OK.
     *
     * @throws Horde_Feed_Exception If an error occurs, an Horde_Feed_Exception will
     * be thrown.
     */
    public function delete()
    {
        // Look for link rel="edit" in the entry object.
        $deleteUri = $this->link('edit');
        if (!$deleteUri) {
            throw new Horde_Feed_Exception('Cannot delete entry; no link rel="edit" is present.');
        }

        // DELETE
        do {
            $response = $this->_httpClient->delete($deleteUri);
            switch ((int)$response->code / 100) {
            // Success
            case 2:
                return true;

            // Redirect
            case 3:
                $deleteUri = $response->getHeader('Location');
                continue;

            // Error
            default:
                throw new Horde_Feed_Exception('Expected response code 2xx, got ' . $response->code);
            }
        } while (true);
    }

    /**
     * Save a new or updated Atom entry.
     *
     * Save is used to either create new entries or to save changes to existing
     * ones. If we have a link rel="edit", we are changing an existing entry. In
     * this case we re-serialize the entry and PUT it to the edit URI, checking
     * for a 200 OK result.
     *
     * For posting new entries, you must specify the $postUri parameter to
     * save() to tell the object where to post itself.  We use $postUri and POST
     * the serialized entry there, checking for a 201 Created response. If the
     * insert is successful, we then parse the response from the POST to get any
     * values that the server has generated: an id, an updated time, and its new
     * link rel="edit".
     *
     * @param string $postUri Location to POST for creating new entries.
     *
     * @throws Horde_Feed_Exception If an error occurs, a Horde_Feed_Exception
     * will be thrown.
     */
    public function save($postUri = null)
    {
        $headers = array('Content-Type' => 'application/atom+xml');

        if ($this->id()) {
            // If id is set, look for link rel="edit" in the
            // entry object and PUT.
            $editUri = $this->link('edit');
            if (!$editUri) {
                throw new Horde_Feed_Exception('Cannot edit entry; no link rel="edit" is present.');
            }

            $response = $this->_httpClient->put($editUri, $this->saveXml(), $headers);
            if ($response->code !== 200) {
                throw new Horde_Feed_Exception('Expected response code 200, got ' . $response->code);
            }
        } else {
            if ($postUri === null) {
                throw new Horde_Feed_Exception('PostURI must be specified to save new entries.');
            }
            $response = $this->_httpClient->post($postUri, $this->saveXml(), $headers);
            if ($response->code !== 201) {
                throw new Horde_Feed_Exception('Expected response code 201, got ' . $response->code);
            }
        }

        // Update internal properties using the response body.
        $body = $response->getBody();
        $newEntry = new DOMDocument;
        $parsed = @$newEntry->loadXML($body);
        if (!$parsed) {
            throw new Horde_Feed_Exception('DOMDocument cannot parse XML: ', error_get_last());
        }

        $newEntry = $newEntry->getElementsByTagName($this->_entryElementName)->item(0);
        if (!$newEntry) {
            throw new Horde_Feed_Exception('No root <feed> element found in server response:' . "\n\n" . $body);
        }

        if ($this->_element->parentNode) {
            $oldElement = $this->_element;
            $this->_element = $oldElement->ownerDocument->importNode($newEntry, true);
            $oldElement->parentNode->replaceChild($this->_element, $oldElement);
        } else {
            $this->_element = $newEntry;
        }

        $this->_expireCachedChildren();
    }

    /**
     * Easy access to <link> tags keyed by "rel" attributes.
     * @TODO rationalize this with other __get/__call access
     *
     * If $elt->link() is called with no arguments, we will attempt to
     * return the value of the <link> tag(s) like all other
     * method-syntax attribute access. If an argument is passed to
     * link(), however, then we will return the "href" value of the
     * first <link> tag that has a "rel" attribute matching $rel:
     *
     * $elt->link(): returns the value of the link tag.
     * $elt->link('self'): returns the href from the first <link rel="self"> in the entry.
     *
     * @param string $rel The "rel" attribute to look for.
     * @return mixed
     */
    public function link($rel = null)
    {
        if ($rel === null) {
            return parent::__call('link', null);
        }

        // index link tags by their "rel" attribute.
        $links = parent::__get('link');
        if (!is_array($links)) {
            if ($links instanceof Horde_Xml_Element) {
                $links = array($links);
            } else {
                return $links;
            }
        }

        foreach ($links as $link) {
            if (empty($link['rel'])) {
                continue;
            }
            if ($rel == $link['rel']) {
                return $link['href'];
            }
        }

        return null;
    }
}
