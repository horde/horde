<?php
/**
 * $Id: News.php 1263 2009-02-01 23:25:56Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license inion (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */
class News_Driver {

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Attempts to return a concrete News_Driver instance based on $driver.
     *
     * @param string $driver  The type of the concrete News_Driver subclass
     *                        to return.  The class name is based on the
     *                        storage driver ($driver).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return News_Driver  The newly created concrete News_Driver.
     * @throws Horde_Exception
     */
    static function factory($driver = 'sql', $params = array())
    {
        $class_name = 'News_Driver_' . $driver;
        require_once NEWS_BASE . '/lib/Driver/' . $driver . '.php';

        if (!class_exists($class_name)) {
            throw new Horde_Exception('DRIVER MISSING');
        }

        return new $class_name($params);
    }

    /**
     * Get news
     *
     * @param int    $news news id
     *
     * @return true on succes PEAR_Error on failure
     */
    public function get($id)
    {
        // Admins bypass the cache (can read nonpublished and locked news)
        if (!Horde_Auth::isAdmin('news:admin')) {
            $key = 'news_'  . News::getLang() . '_' . $id;
            $data = $GLOBALS['cache']->get($key, $GLOBALS['conf']['cache']['default_lifetime']);
            if ($data) {
                return unserialize($data);
            }
        }

        $data = $this->_get($id);
        if ($data instanceof PEAR_Error) {
            return $data;
        }

        if (!Horde_Auth::isAdmin('news:admin')) {
            $GLOBALS['cache']->set($key, serialize($data));
        }

        return $data;
    }
}
