<?php
/**
 * Kronolith_Ajax_Imple_TagActions:: handles ajax requests for adding and
 * removing tags from kronolith objects.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Ajax_Imple_TagActions extends Horde_Ajax_Imple_Base
{
    /**
     */
    public function attach()
    {
        Horde::addScriptFile('tagactions.js');
        $dom_id = $this->_params['triggerId'];
        $action = $this->_params['action'];
        $content_id = $this->_params['resource'];
        $content_type = $this->_params['type'];
        $tag_id = !empty($this->_params['tagId']) ? $this->_params['tagId'] : null;
        $endpoint = $this->_getUrl('TagActions', 'kronolith');

        if ($action == 'add') {
            $js = "Event.observe('" . $dom_id . "_" . $content_id . "', 'click', function(event) {addTag('" . $content_id . "', '" . $content_type . "', '" . $endpoint . "'); Event.stop(event)});";
        } elseif ($action == 'delete') {
            $js = "Event.observe('" . $dom_id . "', 'click', function(event) {removeTag('" . $content_id . "', '" . $content_type . "', " . $tag_id . ", '" . $endpoint . "'); Event.stop(event)});";
        }
        Horde::addInlineScript($js, 'window');
    }

    /**
     * Handle the tag related action.
     *
     * If removing a tag, needs a 'resource' which is the local identifier of
     * the kronolith object, a 'type' which should be the string reprentation of
     * the type of object (event/calendar) and 'tags' should be the integer
     * tag_id of the tag to remove.
     */
    public function handle($args, $post)
    {
        require_once dirname(__FILE__) . '/../../base.php';
        global $ansel_storage;

        $request = $args['action'];
        $content = array('id' => $post['resource'], 'type' => $post['type']);
        $tags = $post['tags'];

        // Check perms
        if ($post['type'] == 'calendar') {
            $cal = $GLOBALS['kronolith_shares']->getShare($post['resource']);
            $perm = $cal->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT);
        } elseif($post['type'] == 'event') {
            $event = Kronolith::getDriver()->getByUID($post['resource']);
            $perm = $event->hasPermission(PERMS_EDIT, Horde_Auth::getAuth());
        }

        if ($perm) {
            /* Get the resource owner */
            $tagger = Kronolith::getTagger();
            switch ($request) {
            case 'add':
                $tagger->tag($post['resource'], $tags, $post['type']);
                break;
            case 'remove':
                $tagger->untag($post['resource'], (int)$tags, $post['type']);
                break;
            }
        }
        return $this->_getTagHtml($tagger, $post['resource'], $post['type']);

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
            $hasEdit = $cal->hasPermission(Horde_Auth::getAuth(), PERMS_EDIT);
        } elseif ($type == 'event') {
            $event = Kronolith::getDriver()->getByUID($id);
            $hasEdit = $event->hasPermission(PERMS_EDIT, Horde_Auth::getAuth());
        }

        foreach ($tags as $tag_id => $tag) {
            $html .= '<li class="panel-tags">' .  $tag . ($hasEdit ? '<a href="#" onclick="removeTag(\'' . $id . '\', \'' . $type . '\',' . $tag_id . ', \'' . Horde::url('imple.php', true) . '\'); Event.stop(event)" id="remove' . md5($id . $tag_id) . '">' . Horde::img('delete-small.png', _("Remove Tag"), '', $GLOBALS['registry']->getImageDir('horde')) . '</a>' : '') . '</li>';
        }

        return $html;
    }

}
