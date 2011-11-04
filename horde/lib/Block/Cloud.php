<?php
/**
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde
 */
class Horde_Block_Cloud extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);
        $this->_name = _("Tag Cloud");
    }

    /**
     */
    protected function _content()
    {
        $cloud = new Horde_Core_Ui_TagCloud();
        foreach ($this->_getTags() as $tag) {
            $cloud->addElement(
                $tag['tag_name'], '#', $tag['count'], null,
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
                    $results = array_merge(
                        $results,
                        $GLOBALS['registry']->call($api . '/listTagInfo', array(null, $GLOBALS['registry']->getAuth())));
                } catch (Horde_Exception $e) {}
            }
        }

        return $results;
    }

}
