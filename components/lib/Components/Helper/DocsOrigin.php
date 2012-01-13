<?php
/**
 * Components_Helper_DocOrigin:: deals with a DOCS_ORIGIN file.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Helper_DocOrigin:: deals with a DOCS_ORIGIN file.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Helper_DocsOrigin
{
    /**
     * Path to the DOCS_ORIGIN file.
     *
     * @var string
     */
    private $_docs_origin;

    /**
     * The HTTP client for remote access.
     *
     * @var Horde_Http_Client
     */
    private $_client;

    /**
     * The list of remote documents. Keys represent the local target positions,
     * the values indicate the remote location.
     *
     * @var array
     */
    private $_documents;

    /**
     * Constructor.
     *
     * @param string $docs_origin Path to the DOCS_ORIGIN file.
     */
    public function __construct($docs_origin, Horde_Http_Client $client)
    {
        $this->_docs_origin = $docs_origin;
        $this->_client = $client;
    }

    /**
     * Parse the instructions from the file.
     *
     * @return NULL
     */
    private function _parse()
    {
        if ($this->_documents === null) {
            $this->_documents = array();
            $rst = file_get_contents($this->_docs_origin[0]);
            if (preg_match_all('/^:`([^:]*)`_:(.*)$/m', $rst, $matches)) {
                foreach ($matches[1] as $match) {
                    if (preg_match('#^.. _' . $match . ':(.*)$#m', $rst, $url)) {
                        $this->_documents[$match] = trim($url[1]);
                    }
                }
            }
        }
        return $this->_documents;
    }

    /**
     * Return the list of documents that will be fetched.
     *
     * @return array The list of remote documents.
     */
    public function getDocuments()
    {
        return $this->_parse();
    }

    /**
     * Fetch the remote documents.
     *
     * @param Components_Output $output The output handler.
     *
     * @return NULL
     */
    public function fetchDocuments(Components_Output $output)
    {
        foreach ($this->_parse() as $local => $remote) {
            $this->_fetchDocument($remote, $local, $output);
        }
    }

    /**
     * Fetch the given remote document into a local target path.
     *
     * @param string            $remote  The remote URI.
     * @param string            $local   The local target path.
     * @param Components_Output $output  The output handler.
     *
     *
     * @return NULL
     */
    public function _fetchDocument($remote, $local, Components_Output $output)
    {
        $this->_client->{'request.timeout'} = 60;
        $content = stream_get_contents($this->_client->get($remote)->getStream());
        $content = preg_replace('#^(\.\. _`([^`]*)`: )((?!http://).*)#m', '\1\2', $content); 
        file_put_contents(
            $this->_docs_origin[1] . '/' . $local,
            $content
        );
        $output->ok(
            sprintf(
                'Fetched remote %s into %s!',
                $remote,
                $this->_docs_origin[1] . '/' . $local
            )
        );
    }
}