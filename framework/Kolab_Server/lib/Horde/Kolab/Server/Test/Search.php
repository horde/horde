<?php
/**
 * A class for simulating a Kolab user database search result.
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
 * A class for simulating a Kolab user database search result.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Test_Search
{

    /**
     * The result data.
     *
     * @var array
     */
    protected $data;

    /**
     * Construct a new instance of this class.
     *
     * @param array $the search result.
     */
    public function __construct($data = array())
    {
        $this->data = $data;
    }

    /**
     * Return the result.
     *
     * @return array The result dataset.
     */
    public function as_struct()
    {
        return $this->data;
    }
}
