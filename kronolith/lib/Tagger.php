<?php
/**
 * Kronolith interface to the Horde_Content tagger
 *
 *
 */

// Note:: Autoloading depends on there being a registry entry for content
Horde_Autoloader::addClassPattern('/^Content_/',
                                  $GLOBALS['registry']->get('fileroot', 'content') . '/lib/');

class Kronolith_Tagger {

    protected static $_type_ids = array();
    protected static $_tagger;

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

            self::$_tagger = new Content_Tagger($context);
            $types = $type_mgr->ensureTypes(array('calendar', 'event'));
            self::$_type_ids = array('calendar' => $types[0], 'event' => $types[1]);
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
        self::$_tagger->tag(Auth::getAuth(),
                   array('object' => $localId,
                         'type' => self::$_type_ids[$content_type]),
                   $tags);
    }

    /**
     * Retrieve the tags on a given object(s)
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
var_dump($tags);
        return $tags;
    }

    public function untag($localId, $tag, $content_type = 'event')
    {
        //self::$_tagger->
    }

    /**
     * tag search - return all types of content we care about tagged with
     * the passed in tags
     *
     * @param array $tags  An array of tag ids.
     */
    public function search($tags, $content_type = null)
    {
        $this->getObjects(array('tagId' => $tags, ''));
    }

}
