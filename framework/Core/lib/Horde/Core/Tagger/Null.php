<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Null driver for the Horde_Content tagger.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Core_Tagger_Null extends Horde_Core_Tagger
{
    /**
     */
    public function __construct()
    {
    }

    /**
     */
    public function tag($localId, $tags, $owner, $content_type = null)
    {
    }

    /**
     */
    public function getTags($localId, $type = null)
    {
        return array();
    }

    /**
     */
    public function untag($localId, $tags, $content_type = null)
    {
    }

    /**
     */
    public function replaceTags($localId, $tags, $owner, $content_type = null)
    {
    }

    /**
     */
    public function listTags($token)
    {
        return array();
    }

    /**
     */
    public function getCloud($user, $limit = 5, $all = false)
    {
        return array();
    }

    /**
     */
    public function getTagCountsByObjects(array $ids, $type = null)
    {
        return array();
    }

    /**
     */
    public function browseTags($tags, $user)
    {
        return array();
    }

    /**
     */
    public function getTagIds($tags)
    {
        return array();
    }

    /**
     */
    public function getTagInfo(
        $tags = null, $limit = 500, $type = null, $user = null
    )
    {
        return array();
    }

    /**
     */
    public function search($tags, $filter = array())
    {
        return array();
    }

}
