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

    var $_tagapis = array('images', 'news');

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
        global $registry;
        Horde::addScriptFile('prototype.js', 'horde');

        $cloud = new Horde_Ui_TagCloud();
        if (!is_a($cloud, 'PEAR_Error')) {
            $tags = $this->_getTags();
            foreach ($tags as $tag) {
                $cloud->addElement($tag['tag_name'], '#', $tag['total'],
                                   null,
                                   'doSearch(\'' . $tag['tag_name'] . '\');');
            }
                $html = Horde_Util::bufferOutput('include',
                                           HORDE_TEMPLATES . '/block/cloud.inc');

                $html .= '<div>&nbsp;' .
                         Horde::img('loading.gif', '', array('style' => 'display:none;', 'id' => 'cloudloadingimg')) .
                         '</div>' . $cloud->buildHTML() .
                         '<div id="cloudsearch"></div>';
        } else {
           $html = $cloud->getMessage();
        }

        return $html;
    }


    function _getTags()
    {
        global $registry;
        $results = array();

        foreach ($this->_tagapis as $api) {
            $methods = $registry->listMethods($api);
            if (array_search($api . '/listTagInfo', $methods)) {
                $result = $registry->call($api . '/listTagInfo', array());
                if (!is_a($result, 'PEAR_Error')) {
                    $results = array_merge($results, $result);
                }
            }
        }
        return $results;
    }

}
