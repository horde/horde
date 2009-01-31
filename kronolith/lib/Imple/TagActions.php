<?php
/**
 * Kronolith_Imple_TagActions:: Class to handle ajax requests for adding and
 * removing tags from kronolith objects.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Kronolith
 */
class Kronolith_Imple_TagActions extends Kronolith_Imple
{
    public function attach()
    {
        // TODO: HACK! attach is called in the contructor which means that even
        // if we are only handling the request, we try to attach().
        if (count($this->_params) == 0) {
            return;
        }
        Horde::addScriptFile('tagactions.js');
        $dom_id = $this->_params['triggerId'];
        $action = $this->_params['action'];
        $content_id = $this->_params['resource'];
        $content_type = $this->_params['type'];
        $tag_id = $this->_params['tagId'];
        $endpoint = Horde::url('imple.php', true);

        if ($action == 'add') {
            $js = "Event.observe('" . $dom_id . "', 'click', function(event) {addTag(); Event.stop(event)});";
        } elseif ($action == 'delete') {
            $js = "Event.observe('" . $dom_id . "', 'click', function(event) {removeTag('" . $content_id . "', '" . $content_type . "', " . $tag_id . ", '" . $endpoint . "'); Event.stop(event)});";
        }
        Kronolith::addInlineScript($js, 'window');
    }

    /**
     * Handle the tag related action.
     *
     * If removing a tag, needs a 'resource' which is the local identifier of
     * the kronolith object, a 'type' which should be the string reprentation of
     * the type of object (event/calendar) and 'tags' should be the integer
     * tag_id of the tag to remove.
     */
    public function handle($args)
    {
        global $ansel_storage;

        $request = $args['action'];
        $content = array('id' => $args['resource'], 'type' => $args['type']);
        $tags = $args['tags'];

        // Check perms
        if ($args['type'] == 'calendar') {
            $cal = $GLOBALS['kronolith_shares']->getShare($args['resource']);
            $perm = $cal->hasPermission(Auth::getAuth(), PERMS_EDIT);
        } elseif($args['type'] == 'event') {
            $event = $GLOBALS['kronolith_driver']->getByUID($args['resource']);
            $perm = $event->hasPermission(PERMS_EDIT, Auth::getAuth());
        }

        if ($perm) {
            /* Get the resource owner */
            switch ($request) {
            case 'add':
                //@TODO
                break;
            case 'remove':
                $tagger = new Kronolith_Tagger();
                $tagger->untag($args['resource'], (int)$tags, $args['type']);
                break;
            }
        }
        return $this->_getTagHtml($tagger, $args['resource'], $args['type']);

    }

    /**
     * Generate the HTML for the tag lists to send back to the browser.
     *
     * TODO: This should be made a view helper when we move to using Horde_View
     *
     * @param Kronolith_Tagger $tagger  The tagger object
     * @param string $id                The identifier (share name or event uid)
     * @param string $type              The type of resource (calendar/event)
     *
     * @return string  The HTML
     */
    private function _getTagHtml($tagger, $id, $type)
    {
        $tags = $tagger->getTags($id, 'calendar');
        $js = '';
        $html = '';

        if ($type == 'calendar') {
            $cal = $GLOBALS['kronolith_shares']->getShare($id);
            $hasEdit = $cal->hasPermission(Auth::getAuth(), PERMS_EDIT);
        } elseif ($type == 'event') {
            if ($kronolith_driver->getCalendar() != $cal) {
                $kronolith_driver->open($cal);
            }
            $event = $GLOBALS['kronolith_driver']->getByUID($id);
            $hasEdit = $event->hasPermission(PERMS_EDIT, Auth::getAuth());
        }

        foreach ($tags as $tag_id => $tag) {
            $html .= '<li class="panel-tags">' .  $tag . ($hasEdit ? '<a href="#" onclick="removeTag(\'' . $id . '\', \'' . $type . '\',' . $tag_id . ', \'' . Horde::url('imple.php', true) . '\'); Event.stop(event)" id="remove' . md5($id . $tag_id) . '">' . Horde::img('delete-small.png', _("Remove Tag"), '', $GLOBALS['registry']->getImageDir('horde')) . '</a>' : '') . '</li>';
        }

        return $html;
    }

}
