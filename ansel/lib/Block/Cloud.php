<?php
/**
 * Display Tag Cloud
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 */
class Ansel_Block_Cloud extends Horde_Core_Block
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
    protected function _params()
    {
        return array(
            'count' => array(
                'name' => _("Number of tags to display"),
                'type' => 'int',
                'default' => 20
            )
        );
    }

    /**
     */
    protected function _content()
    {
        /* Get the tags */
        $tags = $GLOBALS['injector']->getInstance('Ansel_Tagger')->getCloud(null, $this->_params['count']);
        if (count($tags)) {
            $cloud = new Horde_Core_Ui_TagCloud();
            foreach ($tags as $id => $tag) {
                $link = Ansel::getUrlFor('view', array('view' => 'Results',
                                                       'tag' => $tag['tag_name']));
                $cloud->addElement($tag['tag_name'], $link, $tag['count']);
            }
            $html = $cloud->buildHTML();
        } else {
            $html = '';
        }
        return $html;
    }

}
