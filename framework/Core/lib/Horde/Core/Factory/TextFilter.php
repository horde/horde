<?php
/**
 * A Horde_Injector:: based Horde_Text_Filter_Base:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Text_Filter_Base:: factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_TextFilter extends Horde_Core_Factory_Base
{
    /**
     * Return the Horde_Text_Filter_Base:: instance.
     *
     * @param string $driver  Either a driver name, or the full class name to
     *                        use.
     * @param array $params   A hash containing any additional configuration
     *                        parameters a subclass might need.
     *
     * @return Horde_Text_Filter_Base  The singleton instance.
     * @throws Horde_Text_Filter_Exception
     */
    public function create($driver, array $params = array())
    {
        list($driver, $params) = $this->_getDriver($driver, $params);
        return Horde_Text_Filter::factory($driver, $params);
    }

    /**
     * Applies a set of patterns to a block of text.
     *
     * @param string $text    The text to filter.
     * @param mixed $filters  The list of filters (or a single filter).
     * @param mixed $params   The list of params to use with each filter.
     *
     * @return string  The transformed text.
     */
    public function filter($text, $filters = array(), $params = array())
    {
        if (!is_array($filters)) {
            $filters = array($filters);
            $params = array($params);
        }

        $filter_list = array();
        $params = array_values($params);

        foreach (array_values($filters) as $num => $filter) {
            list($driver, $driv_param) = $this->_getDriver($filter, isset($params[$num]) ? $params[$num] : array());
            $filter_list[$driver] = $driv_param;
        }

        return Horde_Text_Filter::filter($text, array_keys($filter_list), array_values($filter_list));
    }

    /**
     * Gets the driver/params for a given base Horde_Text_Filter driver.
     *
     * @param string $driver  Either a driver name, or the full class name to
     *                        use.
     * @param array $params   A hash containing any additional configuration
     *                        parameters a subclass might need.
     *
     * @return array  Driver as the first value, params list as the second.
     */
    protected function _getDriver($driver, $params)
    {
        $lc_driver = Horde_String::lower($driver);

        switch ($lc_driver) {
        case 'bbcode':
            $driver = 'Horde_Core_Text_Filter_Bbcode';
            break;

        case 'emails':
            $driver = 'Horde_Core_Text_Filter_Emails';
            break;

        case 'emoticons':
            $driver = 'Horde_Core_Text_Filter_Emoticons';
            break;

        case 'highlightquotes':
            $driver = 'Horde_Core_Text_Filter_Highlightquotes';
            break;

        case 'linkurls':
            if (!isset($params['callback'])) {
                $params['callback'] = 'Horde::externalUrl';
            }
            break;

        case 'text2html':
            $param_copy = $params;
            foreach (array('emails', 'linkurls', 'space2html') as $val) {
                if (!isset($params[$val])) {
                    $tmp = $this->_getDriver($val, $param_copy);
                    $params[$val] = array(
                        $tmp[0] => $tmp[1]
                    );
                }
            }
            break;
        }

        return array($driver, $params);
    }

}
