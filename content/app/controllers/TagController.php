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
        // Routes enforce POST or PUT only, but double-check here.
    }

    /**
     * Remove a tag
     */
    public function untag()
    {
        // Routes enforce POST or DELETE only, but double-check here.
    }


    protected function _render()
    {
        switch ((string)$this->_request->getFormat()) {
        case 'html':
            $this->render();
            break;

        case 'json':
        default:
            $this->renderText(json_encode($this->results));
            break;
        }
    }

}
