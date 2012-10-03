<?php
/**
 * Imple for adding and removing tags from kronolith objects.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Kronolith
 */
class Kronolith_Ajax_Imple_TagActions extends Horde_Core_Ajax_Imple
{
    /*
     * Constructor parameters:
     *   - action
     *   - resource
     *   - tagId
     *   - type
     */

    /**
     */
    protected function _attach($init)
    {
        global $page_output;

        if ($init) {
            $page_output->addInlineScript(array(
                'document.observe("ImpleParams:' . get_class() . '", function(e) {
                     if (e.memo.action == "add") {
                         e.memo.tags = $F("newtags-input_" + e.memo.resource);
                     }
                 })',
                'document.observe("Imple:' . get_class() . '", function(e) {
                    $(e.memo.tags).update(e.memo.html);
                    if (e.memo.clear) {
                        $(e.memo.clear).setValue("");
                    }
                 })'
            ));
        }

        $args = array(
            'action' => $this->_params['action'],
            'resource' => $this->_params['resource'],
            'type' => $this->_params['type']
        );

        return array_filter(array_merge($args, array(
            'tag_id' => empty($this->_params['tagId']) ? null : $this->_params['tagId']
        )));
    }

    /**
     * If removing a tag, needs a 'resource' which is the local identifier of
     * the kronolith object, a 'type' which should be the string reprentation
     * of the type of object (event/calendar) and 'tags' should be the integer
     * tag_id of the tag to remove.
     *
     * @throws Kronolith_Exception
     */
    protected function _handle(Horde_Variables $vars)
    {
        global $injector, $registry;

        $request = $vars->action;
        $tags = rawurldecode($vars->tags);

        // Check perms only calendar owners may tag a calendar, only event
        // creator can tag an event.
        $cal = $injector->getInstance('Kronolith_Shares')->getShare($vars->resource);
        $cal_owner = $cal->get('owner');
        if ($vars->type == 'event') {
            $event = Kronolith::getDriver()->getByUID($vars->resource);
            $event_owner = $event->creator;
        }

        // $owner is null for system-owned shares, so an admin has perms,
        // otherwise, make sure the resource owner is the current user
        $perm = empty($owner)
            ? $registry->isAdmin()
            : ($owner == $registry->getAuth());

        if ($perm) {
            $tagger = Kronolith::getTagger();

            switch ($request) {
            case 'add':
                $tagger->tag($vars->resource, $tags, $cal_owner, $vars->type);
                if (!empty($event_owner)) {
                    $tagger->tag($vars->resource, $tags, $event_owner, $vars->type);
                }
                break;

            case 'remove':
                $tagger->untag($vars->resource, intval($tags), $vars->type);
                break;
            }
        }

        $res = new stdClass;
        $res->html = $this->_getTagHtml($tagger, $post['resource'], $post['type']);
    }

    /**
     * Generate the HTML for the tag lists to send back to the browser.
     *
     * TODO: This should be made a view helper when we move to using Horde_View
     *
     * @param Kronolith_Tagger $tagger  The tagger object
     * @param string $id                The identifier (share name or event
     *                                  uid).
     * @param string $type              The type of resource (calendar/event)
     *
     * @return string  The HTML.
     *
     * @throws Kronolith_Exception
     */
    private function _getTagHtml($tagger, $id, $type)
    {
        $tags = $tagger->getTags($id, 'calendar');
        $html = '';

        if ($type == 'calendar') {
            $cal = $GLOBALS['injector']->getInstance('Kronolith_Shares')->getShare($id);
            $hasEdit = $cal->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);
        } elseif ($type == 'event') {
            $event = Kronolith::getDriver()->getByUID($id);
            $hasEdit = $event->hasPermission(Horde_Perms::EDIT, $GLOBALS['registry']->getAuth());
        }

        foreach ($tags as $tag_id => $tag) {
            $html .= '<li class="panel-tags">' . htmlspecialchars($tag) . ($hasEdit ? '<a href="#" onclick="removeTag(\'' . $id . '\', \'' . $type . '\', ' . $tag_id . ',\'' . $this->_getUrl('TagActions', 'kronolith') . '\'); Event.stop(event);" id="remove' . md5($id . $tag_id) . '">' . Horde::img('delete-small.png', _("Remove Tag")) . '</a>' : '') . '</li>';
        }

        return $html;
    }

}
