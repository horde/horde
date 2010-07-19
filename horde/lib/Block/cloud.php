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

        $cloud = new Horde_Ui_TagCloud();
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
                $result = $GLOBALS['registry']->call($api . '/listTagInfo');
                if (!is_a($result, 'PEAR_Error')) {
                    $results = array_merge($results, $result);
                }
            }
        }

        return $results;
    }

}
