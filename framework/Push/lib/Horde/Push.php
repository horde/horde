<?php
/**
 * A content element that will be pushed to various recipients.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Push
 */

/**
 * A content element that will be pushed to various recipients.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Push
 */
class Horde_Push
{
    /**
     * Content summary.
     *
     * @var string
     */
    private $_summary = '';

    /**
     * Constructor.
     *
     * @params array $params The parameters that define this content element.
     */
    public function __construct($params = array())
    {
        if (isset($params['summary'])) {
            $this->_summary = $params['summary'];
        }
    }

    /**
     * Return the summary for this content element.
     */
    public function getSummary()
    {
        return $this->_summary;
    }
}
