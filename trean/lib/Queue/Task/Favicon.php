<?php
class Trean_Queue_Task_Favicon implements Horde_Queue_Task
{
    /**
     * Url to be crawled
     * @var string
     */
    protected $_url;

    /**
     * Bookmark id
     * @var integer
     */
    protected $_bookmarkId;

    /**
     * User id
     * @var integer
     */
    protected $_userId;

    /**
     * Page body
     * @var string
     */
    protected $_body;

    /**
     * Page body character set
     * @var string
     */
    protected $_charset;

    /**
     * Constructor
     *
     * @var string  $url         URL to crawl
     * @var integer $bookmarkId  Bookmark id
     * @var integer $userId      Horde integer user id
     * @var string  $body        If already fetched, the body of $url
     * @var string  $charset     If the body is already fetched, the string charset
     */
    public function __construct($url, $bookmarkId, $userId, $body = null, $charset = null)
    {
        $this->_url = $url;
        $this->_bookmarkId = $bookmarkId;
        $this->_userId = $userId;
        $this->_body = $body;
        $this->_charset = $charset;
    }

    /**
     */
    public function run()
    {
        $injector = $GLOBALS['injector'];
        $client = $injector->getInstance('Horde_Http_Client');

        if (!$this->_body) {
            // Fetch full text of $url
            try {
                $page = $client->get($this->_url);
                $this->_body = $page->getBody();
                if ($type = $page->getHeader('Content-Type') &&
                    preg_match('/.*;\s*charset="?([^" ]*)/', $type, $match)) {
                    $this->_charset = $match[1];
                }
            } catch (Horde_Http_Exception $e) {
            }
        }

        $url = parse_url($this->_url);

        if ($favicon = $this->_findByRel($client, $url, $this->_body, $this->_charset)) {
            $this->_storeFavicon($favicon);
        } elseif ($favicon = $this->_findByRoot($client, $url)) {
            $this->_storeFavicon($favicon);
        } elseif ($favicon = $this->_findByPath($client, $url)) {
            $this->_storeFavicon($favicon);
        }
    }

    /**
     * @param Horde_Http_Response_Base $response HTTP response; body of this is the favicon
     */
    protected function _storeFavicon(Horde_Http_Response_Base $response)
    {
        global $injector;

        $gateway = $injector->getInstance('Trean_Bookmarks');
        $bookmark = $gateway->getBookmark($this->_bookmarkId);
        if ($bookmark) {
            $bookmark->favicon_url = $response->uri;
            $bookmark->save(false);
        }

        // Initialize VFS
        $vfs = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Vfs')
            ->create();
        $vfs->writeData('.horde/trean/favicons/',
                        md5($bookmark->favicon_url),
                        $response->getBody(),
                        true);
    }

    protected function _findByRel($client, $url, $body, $charset)
    {
        try {
            $dom = new Horde_Domhtml($body, $charset);
            foreach ($dom as $node) {
                if ($node instanceof DOMElement &&
                    Horde_String::lower($node->tagName) == 'link' &&
                    ($rel = Horde_String::lower($node->getAttribute('rel'))) &&
                    ($rel == 'shortcut icon' || $rel == 'icon')) {
                    $favicon = $node->getAttribute('href');

                    // Make sure $favicon is a full URL.
                    $favicon_url = parse_url($favicon);
                    if (empty($favicon_url['scheme'])) {
                        if (substr($favicon, 0, 1) == '/') {
                            $favicon = $url['scheme'] . '://' . $url['host'] . $favicon;
                        } else {
                            $path = pathinfo($url['path']);
                            $favicon = $url['scheme'] . '://' . $url['host'] . $path['dirname'] . '/' . $favicon;
                        }
                    }

                    try {
                        $response = $client->get($favicon);
                        if ($this->_isValidFavicon($response)) {
                            return $response;
                        }
                    } catch (Horde_Http_Exception $e) {
                    }
                }
            }
        } catch (Exception $e) {
        }
    }

    protected function _findByRoot($client, $url)
    {
        try {
            $response = $client->get($url['scheme'] . '://' . $url['host'] . '/favicon.ico');
            if ($this->_isValidFavicon($response)) {
                return $response;
            }
        } catch (Horde_Http_Exception $e) {
        }
    }

    protected function _findByPath($client, $url)
    {
        if (isset($url['path'])) {
            $path = pathinfo($url['path']);
            if (strlen($path['dirname'])) {
                try {
                    $response = $client->get($url['scheme'] . '://' . $url['host'] . $path['dirname'] . '/favicon.ico');
                    if ($this->_isValidFavicon($response)) {
                        return $response;
                    }
                } catch (Horde_Http_Exception $e) {
                }
            }
        }
    }

    protected function _isValidFavicon($response)
    {
        return ($response->code == 200)
            && (substr($response->getHeader('content-type'), 0, 5) == 'image')
            && (strlen($response->getBody()) > 0);
    }
}
