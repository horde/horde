<?php
/**
 * Collection of prefs UI utilities for use with common preferences.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Core_Prefs_Utils
{
    /**
     * Determines parameters needed to do an address search
     *
     * @return array  An array with two keys: 'sources' and 'fields'.
     */
    static public function getAddressbookSearchParams()
    {
        $src = explode("\t", $GLOBALS['prefs']->getValue('search_sources'));
        if ((count($src) == 1) && empty($src[0])) {
            $src = array();
        }

        $fields = array();
        if (($val = $GLOBALS['prefs']->getValue('search_fields'))) {
            $field_arr = explode("\n", $val);
            foreach ($field_arr as $field) {
                $field = trim($field);
                if (!empty($field)) {
                    $tmp = explode("\t", $field);
                    if (count($tmp) > 1) {
                        $source = array_splice($tmp, 0, 1);
                        $fields[$source[0]] = $tmp;
                    }
                }
            }
        }

        return array(
            'fields' => $fields,
            'sources' => $src
        );
    }

}
