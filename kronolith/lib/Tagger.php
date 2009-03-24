<?php
/**
 * Kronolith interface to the Horde_Content tagger
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Kronolith
 */

// NOTE: For now, autoloading depends on there being a registry entry for the
//       'content' application.

Horde_Autoloader::addClassPattern('/^Content_/',
                                  $GLOBALS['registry']->get('fileroot', 'content') . '/lib/');

class Kronolith_Tagger
{

    /**
     * Local cache of the type name => ids from Content, so we don't have to
     * query for them each time.
     *
     * @var array
     */
    protected static $_type_ids = array();

    /**
     * @var Content_Tagger
     */
    protected static $_tagger;

    /**
     * Constructor - needs to instantiate the Content_Tagger object if it's not
     * already present.
     */
    public function __construct()
    {
        if (is_null(self::$_tagger)) {
            // Set up the context for the tagger and related content classes
            $GLOBALS['conf']['sql']['adapter'] = $GLOBALS['conf']['sql']['phptype'] == 'mysqli' ?
                             'mysqli' :
                             'pdo_' . $GLOBALS['conf']['sql']['phptype'];

            Horde_Db::setAdapter(Horde_Db_Adapter::factory($GLOBALS['conf']['sql']));

            $context = array('dbAdapter' => Horde_Db::getAdapter());
            $user_mgr = new Content_Users_Manager($context);
            $type_mgr = new Content_Types_Manager($context);

            // Objects_Manager requires a Types_Manager
            $context['typeManager'] = $type_mgr;
            $object_mgr = new Content_Objects_Manager($context);

            // Create the Content_Tagger
            $context['userManager'] = $user_mgr;
            $context['objectManager'] = $object_mgr;

            // Cache the object statically
            self::$_tagger = new Content_Tagger($context);
            $types = $type_mgr->ensureTypes(array('calendar', 'event'));

            // Remember the types to avoid having Content query them again.
            self::$_type_ids = array('calendar' => (int)$types[0], 'event' => (int)$types[1]);
        }
    }

    /**
     * Tag a kronolith object with any number of tags.
     *
     * @param string $localId       The identifier of the kronolith object.
     * @param mixed $tags           Either a single tag string or an array of tags.
     * @param string $content_type  The type of object we are tagging (event/calendar).
     *
     * @return void
     */
    public function tag($localId, $tags, $content_type = 'event')
    {
        // If we don't have an array - split the string.
        if (!is_array($tags)) {
            $tags = self::$_tagger->splitTags($tags);
        }

        self::$_tagger->tag(Auth::getAuth(),
                   array('object' => $localId,
                         'type' => self::$_type_ids[$content_type]),
                   $tags);
    }

    /**
     * Retrieve the tags on given object(s).
     *
     * @param string $localId  The identifier of the kronolith object.
     * @param string $type     The type of object $localId represents.
     *
     * @return a tag_id => tag_name hash.
     */
    public function getTags($localId, $type = 'event')
    {
        if (!is_array($localId)) {
            $localId = array($localId);
        }
        $tags = array();
        foreach ($localId as $id) {
            $tags = $tags + self::$_tagger->getTags(array('objectId' => array('object' => $id, 'type' => $type)));
        }

        return $tags;
    }

    /**
     * Remove a tag from a kronolith object. Removes *all* tags - regardless of
     * the user that added the tag.
     *
     * @param string $localId       The kronolith object identifier.
     * @param mixed $tag            Either a tag_id, tag_name or an array of
     *                              ids or names to remove.
     * @param string $content_type  The type of object that $localId represents.
     *
     * @return void
     */
    public function untag($localId, $tag, $content_type = 'event')
    {
        self::$_tagger->removeTagFromObject(
            array('object' => $localId, 'type' => self::$_type_ids[$content_type]),
            $tag);
    }

    /**
     * Tag the given resource with *only* the tags provided, removing any tags
     * that are already present but not in the list.
     *
     * @param $localId
     * @param $tags
     * @param $content_type
     *
     * @return void
     */
    public function replaceTags($localId, $tags, $content_type = 'event')
    {
        // First get a list of existing tags.
        $existing_tags = $this->getTags($localId, $content_type);

        // If we don't have an array - split the string.
        if (!is_array($tags)) {
            $tags = self::$_tagger->splitTags($tags);
        }
        $remove = array();
        foreach ($existing_tags as $tag_id => $existing_tag) {
            $found = false;
            foreach ($tags as $tag_text) {
                //if ($existing_tag == String::lower($tag_text, true)) {
                if ($existing_tag == $tag_text) {
                    $found = true;
                    break;
                }
            }
            // Remove any tags that were not found in the passed in list.
            if (!$found) {
                $remove[] = $tag_id;
            }
        }

        $this->untag($localId, $remove, $content_type);
        $add = array();
        foreach ($tags as $tag_text) {
            $found = false;
            foreach ($existing_tags as $existing_tag) {
                if ($tag_text == $existing_tag) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $add[] = $tag_text;
            }
        }

        $this->tag($localId, $add, $content_type);
    }

    /**
     * @TODO
     * @param array $tags  An array of tag ids.
     */
    public function search($tags, $content_type = null)
    {
        //TODO
    }

    /**
     * List tags beginning with $token.  Used for autocomplete code.
     *
     * @param $token
     * @return unknown_type
     */
    public function listTags($token)
    {
        return self::$_tagger->getTags(array('q' => $token));
    }

    public function getCloud($user, $limit = 5)
    {
        return self::$_tagger->getTagCloud(array('userId' => $user,
                                                  'limit' => $limit));
    }
}
