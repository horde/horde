<?php

$block_name = _("Tag Cloud");

/**
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Block
 */
class Horde_Block_Horde_cloud extends Horde_Block {

    /**
     * Whether this block has changing content.
     */
    var $updateable = false;

    /**
     * @var string
     */
    var $_app = 'horde';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Tag Cloud");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content.
     */
    function _content()
    {
        Horde::addScriptFile('prototype.js', 'horde');

        $cloud = new Horde_Ui_TagCloud();
        foreach ($this->_getTags() as $tag) {
            $cloud->addElement($tag['tag_name'], '#', $tag['total'],
                               null,
                               'doSearch(\'' . $tag['tag_name'] . '\');');
        }

        return Horde_Util::bufferOutput('include', HORDE_TEMPLATES . '/block/cloud.inc')
            . '<div>&nbsp;'
            . Horde::img('loading.gif', '', array('style' => 'display:none;', 'id' => 'cloudloadingimg'))
            . '</div>' . $cloud->buildHTML()
            . '<div id="cloudsearch"></div>';
    }

    function _getTags()
    {
        $results = array();
        foreach ($GLOBALS['registry']->listAPIs() as $api) {
            if ($GLOBALS['registry']->hasMethod($api . '/listTagInfo')) {
                var_dump($api . '/listTagInfo');
                $result = $registry->call($api . '/listTagInfo');
                if (!is_a($result, 'PEAR_Error')) {
                    $results = array_merge($results, $result);
                }
            }
        }
        return $results;
    }

}
