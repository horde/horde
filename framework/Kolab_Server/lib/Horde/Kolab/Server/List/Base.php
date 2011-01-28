<?php
/**
 * A server list implementation.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * A server list implementation.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_List_Base
implements Horde_Kolab_Server_List_Interface
{
    /**
     * List all objects of a specific type.
     *
     * @param string $type   The type of the objects to be listed
     * @param array  $params Additional parameters.
     *
     * @return array An array of Kolab objects.
     *
     * @throws Horde_Kolab_Server_Exception
     *
     * @todo Sorting
     */
    public function listObjects($type, $params = null)
    {
        $result   = Horde_Kolab_Server_Object::loadClass($type);
        $vars     = get_class_vars($type);
        $criteria = call_user_func(array($type, 'getFilter'));
        $filter   = $this->searchQuery($criteria);
        $sort     = $vars['sort_by'];

        if (isset($params['sort'])) {
            $sort = $params['sort'];
        }

        $options = array('scope' => 'sub');
        if (isset($params['attributes'])) {
            $options['attributes'] = $params['attributes'];
        } else {
            $options['attributes'] = $this->getAttributes($type);
        }

        $data = $this->search($filter, $options, $base);
        if (empty($data)) {
            return array();
        }

        if ($sort) {
            /* @todo: sorting */
            /* $data = $result->as_sorted_struct(); */
            /*$this->sort($result, $sort); */
        }

        if (isset($params['from'])) {
            $from = $params['from'];
        } else {
            $from = -1;
        }

        if (isset($params['to'])) {
            $sort = $params['to'];
        } else {
            $to = -1;
        }

        if (!empty($vars['required_group'])) {
            $required_group = new Horde_Kolab_Server_Object_Kolabgroupofnames(
                $this,
                null,
                $vars['required_group']
            );
        }

        $objects = array();
        foreach ($data as $uid => $entry) {
            if (!empty($vars['required_group'])) {
                if (!$required_group->exists() || !$required_group->isMember($uid)) {
                    continue;
                }
            }
            $objects[$uid] = &Horde_Kolab_Server_Object::factory(
                $type, $uid, $this, $entry
            );
        }
        return $objects;
    }
}