<?php
/**
 * @package Content
 */

/**
 * @package Content
 */
class TagController extends Content_ApplicationController
{
    /**
     */
    public function searchTags()
    {
        $this->results = $this->tagger->getTags(array(
            'q' => $this->params->q,
            'typeId' => $this->params->typeId,
            'userId' => $this->params->userId,
            'objectId' => $this->params->objectId,
        ));

        $this->_render();
    }

    /**
     */
    public function recentTags()
    {
        $this->results = $this->tagger->getRecentTags(array(
            'limit' => 10,
            'typeId' => $this->params->typeId,
            'objectId' => $this->params->objectId,
        ));

        $this->_render();
    }

    public function searchUsers()
    {
    }

    public function recentUsers()
    {
    }

    public function searchObjects()
    {
    }

    public function recentObjects()
    {
    }

    /**
     * Add a tag
     */
    public function tag()
    {
        // The route configuration enforces POST or PUT only, but double-check here.
    }

    /**
     * Remove a tag
     */
    public function untag()
    {
        // The route configuration enforces POST or DELETE only, but double-check here.
    }


    protected function _render()
    {
        switch ((string)$this->_request->getFormat()) {
        case 'html':
            $this->render();
            break;

        case 'atom':
        case 'rss':
            $method = '_' . $this->_action . 'Feed';
            $this->$method();
            break;

        case 'json':
        default:
            $this->renderText(json_encode($this->results));
            break;
        }
    }

    protected function _recentTagsFeed()
    {
        $entries = array();
        foreach ($this->results as $tag) {
            $entries[] = array(
                'id' => 'tag/' . $tag['tag_id'], /* @TODO use routes to get the full URI here */
                'title' => $tag['tag_name'],
                'updated' => $tag['created'],
            );
        }

        $format = $this->_request->getFormat();
        $class = 'Horde_Feed_' . ucfirst((string)$this->_request->getFormat());
        $feed = new $class(array(
            'id' => 'tags/recent', /* @TODO Use routes to get url to this search */
            'title' => 'Recent tags',
            'updated' => $this->_request->getTimestamp(),
            'entry' => $entries,

        ));
        header('Content-type: ' . $format->string);
        $this->renderText($feed->saveXml());
    }

    protected function _recentObjectsFeed()
    {
        $entries = array();
        foreach ($this->results as $object) {
            $entries[] = array(
                'id' => 'object/' . $object['object_id'], /* @TODO use routes to get the full URI here */
                'title' => $object['object_name'],
                'updated' => $object['created'],
            );
        }

        $format = $this->_request->getFormat();
        $class = 'Horde_Feed_' . ucfirst((string)$this->_request->getFormat());
        $feed = new $class(array(
            'id' => 'objects/recent', /* @TODO Use routes to get url to this search */
            'title' => 'Recent objects',
            'updated' => $this->_request->getTimestamp(),
            'entry' => $entries,

        ));
        header('Content-type: ' . $format->string);
        $this->renderText($feed->saveXml());
    }

    protected function _recentUsersFeed()
    {
        $entries = array();
        foreach ($this->results as $user) {
            $entries[] = array(
                'id' => 'user/' . $user['user_id'], /* @TODO use routes to get the full URI here */
                'title' => $user['user_name'],
                'updated' => $user['created'],
            );
        }

        $format = $this->_request->getFormat();
        $class = 'Horde_Feed_' . ucfirst((string)$this->_request->getFormat());
        $feed = new $class(array(
            'id' => 'users/recent', /* @TODO Use routes to get url to this search */
            'title' => 'Recent users',
            'updated' => $this->_request->getTimestamp(),
            'entry' => $entries,

        ));
        header('Content-type: ' . $format->string);
        $this->renderText($feed->saveXml());
    }

}
