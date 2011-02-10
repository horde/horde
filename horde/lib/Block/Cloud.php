<?php
/**
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
class Horde_Block_Cloud extends Horde_Block
{
    /**
     */
    public function getName()
    {
        return _("Tag Cloud");
    }

    /**
     */
    protected function _title()
    {
        return $this->getName();
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
