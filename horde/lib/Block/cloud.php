<?php

$block_name = _("Tag Cloud");

/**
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Block
 */
class Horde_Block_Horde_cloud extends Horde_Block
{
    /**
     * @var string
     */
    protected $_app = 'horde';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    protected function _title()
    {
        return _("Tag Cloud");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content.
     */
    protected function _content()
    {
        Horde::addScriptFile('prototype.js', 'horde');

        $cloud = new Horde_Core_Ui_TagCloud();
        foreach ($this->_getTags() as $tag) {
            $cloud->addElement($tag['tag_name'], '#', $tag['total'],
                               null,
                               'doSearch(\'' . $tag['tag_name'] . '\');');
        }

        Horde::startBuffer();
        include HORDE_TEMPLATES . '/block/cloud.inc';

        return Horde::endBuffer()
            . '<div>&nbsp;'
            . Horde::img('loading.gif', '', array('style' => 'display:none;', 'id' => 'cloudloadingimg'))
            . '</div>' . $cloud->buildHTML()
            . '<div id="cloudsearch"></div>';
    }

    /**
     *
     * @return array
     */
    private function _getTags()
    {
        $results = array();
        foreach ($GLOBALS['registry']->listAPIs() as $api) {
            if ($GLOBALS['registry']->hasMethod($api . '/listTagInfo')) {
                try {
                    $results = array_merge($results, $GLOBALS['registry']->call($api . '/listTagInfo'));
                } catch (Horde_Exception $e) {}
            }
        }

        return $results;
    }

}
