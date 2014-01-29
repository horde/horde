<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Iterator filter for the IMP_Ftree object that returns the ancestors for an
 * element.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_IteratorFilter_Ancestors extends IMP_Ftree_IteratorFilter
{
    /**
     */
    static public function create($mask = 0, $elt = null)
    {
        $elts = array();

        while ($elt = $elt->parent) {
            $elts = array_merge($elt->child_list, $elts);
        }

        return parent::create($mask | self::NO_CHILDREN, $elts);
    }

}
