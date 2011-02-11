<?php
/**
 * Display Tag Cloud
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 */
class Jonah_Block_Cloud extends Horde_Core_Block
{
    /**
     */
    public function getName()
    {
        return _("Tag Cloud");
    }

    /**
     */
    protected function _params()
    {
        return array(
            'results_url' => array(
                'name' => _("Results URL"),
                'type' => 'text',
                'default' => Horde::url('stories/results.php?tag_id=@id@'),
            ),
        );
    }

    protected function _content()
    {
        /* Get the tags */
        $tags = $GLOBALS['injector']->getInstance('Jonah_Driver')->listTagInfo();
        if (count($tags)) {
            $cloud = new Horde_Core_Ui_TagCloud();
            foreach ($tags as $id => $tag) {
                $cloud->addElement($tag['tag_name'], str_replace(array('@id@', '@tag@'), array($id, $tag['tag_name']), $this->_params['results_url']), $tag['total']);
            }
            $html = $cloud->buildHTML();
        } else {
            $html = '';
        }

        return $html;
    }
}
