<?php
/**
 * @package Jonah
 */

/**
 * Horde_Form
 */
require_once 'Horde/Form.php';

/**
 * Horde_Form_Action
 */
require_once 'Horde/Form/Action.php';

/**
 * This class extends Horde_Form to provide the form to add/edit
 * stories.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @package Jonah
 */
class StoryForm extends Horde_Form
{
    /**
     */
    function StoryForm(&$vars)
    {
        parent::Horde_Form($vars, $vars->get('story_id') ? _("Edit Story") : _("Add New Story"));

        $this->setButtons(_("Save"), true);
        $this->addHidden('', 'channel_id', 'int', false);
        $this->addHidden('', 'story_id', 'int', false);
        $this->addHidden('', 'story_read', 'int', false);
        $this->addVariable(_("Story Title (Headline)"), 'story_title', 'text', true);
        $this->addVariable(_("Short Description"), 'story_desc', 'longtext', true, false, null, array(2, 80));
        $this->addVariable(_("Publish Now?"), 'publish_now', 'boolean', false);

        $published = $vars->get('story_published');
        if ($published) {
            $date_params = array(min(date('Y', $published), date('Y')),
                                 max(date('Y', $published), date('Y') + 10));
        } else {
            $date_params = array();
        }

        $d = &$this->addVariable(_("Or publish on this date:"), 'publish_date', 'monthdayyear', false, false, null, $date_params);
        $d->setDefault($published);

        $t = &$this->addVariable('', 'publish_time', 'hourminutesecond', false);
        $t->setDefault($published);

        $v = &$this->addVariable(_("Story body type"), 'story_body_type', 'enum', false, false, null, array(Jonah::getBodyTypes()));
        $v->setAction(Horde_Form_Action::factory('submit'));
        $v->setOption('trackchange', true);

        /* If no body type specified, default to one. */
        $body_type = $vars->get('story_body_type');
        if (empty($body_type)) {
            $body_type = Jonah::getDefaultBodyType();
            $vars->set('story_body_type', $body_type);
        }

        /* Set up the fields according to what the type of body requested. */
        if ($body_type == 'text') {
            $this->addVariable(_("Full Story Text"), 'story_body', 'longtext', false, false, null, array(15, 80));
        } elseif ($body_type == 'richtext') {
            $this->addVariable(_("Full Story Text"), 'story_body', 'longtext', false, false, null, array(20, 80, array('rte')));
        }

        $this->addVariable(_("Tags"), 'story_tags', 'text', false, false, _("Enter keywords to tag this story, separated by commas"));
        /* Only show URL insertion if it has been enabled in config. */
        if (in_array('links', $GLOBALS['conf']['news']['story_types'])) {
            $this->addVariable(_("Story URL"), 'story_url', 'text', false, false, _("If you enter a URL without a full story text, clicking on the story will send the reader straight to the URL, otherwise it will be shown at the end of the full story."));
        }
    }

    /**
     */
    function getInfo(&$vars, &$info)
    {
        parent::getInfo($vars, $info);

        /* Build release date. */
        if (!empty($info['publish_now'])) {
            $info['story_published'] = time();
        } elseif (!empty($info['publish_date'])) {
            $info['story_published'] = mktime(
                (int)$info['publish_time']['hour'],
                (int)$info['publish_time']['minute'],
                0,
                date('n', $info['publish_date']),
                date('j', $info['publish_date']),
                date('Y', $info['publish_date']));
        } else {
            $info['story_published'] = null;
        }

        unset($info['publish_now']);
        unset($info['publish_date']);
        unset($info['publish_time']);
    }

}
