<?php
/**
 * Ansel_Search_exif Provides an interface for searching image exif data.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Search_exif {

    /**
     * Constructor
     *
     * @param array $params
     * @return Ansel_Search_exif
     */
    public function __construct($params = array())
    {
        $this->_type = 'exif';
    }

    /**
     * retrieve a slice of the current search
     *
     * @param unknown_type $page
     * @param unknown_type $perpage
     */
    function getSlice($page, $perpage)
    {
    }

    /**
     * Get the total number of resources that match
     */
    function count()
    {
    }
}
