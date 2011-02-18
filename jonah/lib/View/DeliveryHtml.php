<?php
/**
 * Script to handle requests for html delivery of stories.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Jan Schneider <jan@horde.org>
 */
class Jonah_View_DeliveryHtml extends Jonah_View_Base
{
    /**
     * $registry
     * $notification
     * $conf
     * $criteria
     *
     */
    public function run()
    {
        extract($this->_params, EXTR_REFS);
        $templates = Horde::loadConfiguration('templates.php', 'templates', 'jonah');

        /* Get requested channel. */
        try {
            $channel = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannel($criteria['feed']);
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
            $notification->push(_("Invalid channel."), 'horde.error');
            Horde::url('delivery/index.php', true)->redirect();
            exit;
        }

        $title = sprintf(_("HTML Delivery for \"%s\""), $channel['channel_name']);

        $options = array();
        foreach ($templates as $key => $info) {
            $options[] = '<option value="' . $key . '"' . ($key == $criteria['format'] ? ' selected="selected"' : '') . '>' . $info['name'] . '</option>';
        }

        $template = new Horde_Template();
        $template->setOption('gettext', 'true');
        $template->set('url', Horde::selfUrl());
        $template->set('session', Horde_Util::formInput());
        $template->set('channel_id', $criteria['feed']);
        $template->set('channel_name', $channel['channel_name']);
        $template->set('format', $criteria['format']);
        $template->set('options', $options);

        // @TODO: This is ugly. storage driver shouldn't be rendering any display
        // refactor this to use individual views possibly with a choice of different templates
        $template->set('stories', $GLOBALS['injector']->getInstance('Jonah_Driver')->renderChannel($criteria['feed'], $criteria['format']));
        $template->set('menu', Jonah::getMenu('string'));

        // Buffer the notifications and send to the template
        Horde::startBuffer();
        $GLOBALS['notification']->notify(array('listeners' => 'status'));
        $template->set('notify', Horde::endBuffer());

        require $registry->get('templates', 'horde') . '/common-header.inc';
        echo $template->fetch(JONAH_TEMPLATES . '/delivery/html.html');
        require $registry->get('templates', 'horde') . '/common-footer.inc';
    }

}