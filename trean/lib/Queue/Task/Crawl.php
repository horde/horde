<?php
class Trean_Queue_Task_Crawl implements Horde_Queue_Task
{
    /**
     * Url to be crawled
     * @var string
     */
    protected $_url;

    /**
     * User-entered page title
     * @var string
     */
    protected $_userTitle;

    /**
     * User-entered page description
     * @var string
     */
    protected $_userDesc;

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
     * Constructor
     *
     * @var string $url          URL to crawl
     * @var string $userTitle    User-supplied title for the bookmark
     * @var string $userDesc     User-supplied description
     * @var integer $bookmarkId  Bookmark id
     * @var integer $userId      Horde integer user id
     */
    public function __construct($url, $userTitle, $userDesc, $bookmarkId, $userId)
    {
        $this->_url = $url;
        $this->_userTitle = $userTitle;
        $this->_userDesc = $userDesc;
        $this->_bookmarkId = $bookmarkId;
        $this->_userId = $userId;
    }

    /**
     */
    public function run()
    {
        $injector = $GLOBALS['injector'];

        // Get Horde_Http_Client
        $client = $injector->getInstance('Horde_Http_Client');

        // Fetch full text of $url
        $page = $client->get($this->_url);

        // submit text to ElasticSearch, under $userId's index
        $indexer = $injector->getInstance('Content_Indexer');
        $indexer->index('horde-user-' . $this->_userId, 'trean-bookmark', $this->_bookmarkId, json_encode(array(
            'title' => $this->_userTitle,
            'description' => $this->_userDesc,
            'url' => $this->_url,
            'headers' => $page->headers,
            'body' => $page->getBody(),
        )));

        // update bookmark_http_status
        $gateway = $injector->getInstance('Trean_Bookmarks');
        $bookmark = $gateway->getBookmark($this->_bookmarkId);
        if ($bookmark->http_status != $page->code) {
            $bookmark->http_status = $page->code;
            $bookmark->save();
        }

        // @TODO: crawl resources from the page to make a fully local version
        // @TODO: favicon
    }
}
