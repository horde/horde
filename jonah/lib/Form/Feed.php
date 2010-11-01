<?php
/**
 * This class extends Horde_Form to provide the form to add/edit
 * feeds.
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
class Jonah_Form_Feed extends Horde_Form
{
    /**
     */
    function __construct(&$vars)
    {
        $channel_id = $vars->get('channel_id');
        $editing = (!empty($channel_id));

        parent::Horde_Form($vars, ($editing ? _("Edit Feed") : _("New Feed")));

        $this->addHidden('', 'channel_id', 'int', false);
        $this->addHidden('', 'old_channel_type', 'text', false);

        $select_type =& $this->addVariable(_("Type"), 'channel_type', 'enum', true, false, null, array(Jonah::getAvailableTypes()));
        $select_type->setDefault(Jonah::INTERNAL_CHANNEL);
        $select_type->setHelp('feed-type');
        $select_type->setAction(Horde_Form_Action::factory('submit'));

        $this->addVariable(_("Name"), 'channel_name', 'text', true);
        $this->addVariable(_("Extra information for this feed type"), 'extra_info', 'header', false);
    }

    /**
     */
    function setExtraFields($channel_id = null)
    {
        $this->addVariable(_("Description"), 'channel_desc', 'text', false);
        $this->addVariable(
            _("Channel Slug"), 'channel_slug', 'text', true, false,
           sprintf(_("Slugs allows direct access to this channel's content by visiting: %s. <br /> Slug names may contain only letters, numbers or the _ (underscore) character."),
                    Horde::url('slugname')),
            array('/^[a-zA-Z1-9_]*$/'));

        $this->addVariable(_("Include full story content in syndicated feeds?"), 'channel_full_feed', 'boolean', false);
        $this->addVariable(_("Channel URL if not the default one. %c gets replaced by the feed ID."), 'channel_link', 'text', false);
        $this->addVariable(_("Channel URL for further pages, if not the default one. %c gets replaced by the feed ID, %n by the story offset."), 'channel_page_link', 'text', false);
        $this->addVariable(_("Story URL if not the default one. %c gets replaced by the feed ID, %s by the story ID."), 'channel_story_url', 'text', false);
    }

}
