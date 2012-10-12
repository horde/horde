<?php
/**
 * The abstract class that all quota drivers inherit from.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
abstract class IMP_Quota
{
    /**
     * Driver parameters.
     *
     * @var array
     */
    protected $_params = array(
        'unit' => 'MB'
    );

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     *   - format: (string) The formats of the quota messages in the user
     *             interface. Must be a hash with the four possible elements
     *             'long', 'short', 'nolimit_long', and 'nolimit_short'. The
     *             strings will be passed through sprintf().
     *   - unit: (string) What storage unit the quota messages should be
     *           displayed in. Either 'GB', 'MB', or 'KB'.
     *   - username: (string) The username to query.
     */
    public function __construct(array $params = array())
    {
        $this->_params = array_merge($this->_params, $params);

        $this->_params['format'] = array(
            'short' => isset($this->_params['format']['short'])
                ? $this->_params['format']['short']
                : _("%.0f%% of %.0f %s"),
            'nolimit_short' => isset($this->_params['format']['nolimit_short'])
                ? $this->_params['format']['nolimit_short']
                : _("%.0f %s")
        );
    }

    /**
     * Get quota information (used/allocated), in bytes.
     *
     * @return array  An array with the following keys:
     *   - limit: Maximum quota allowed
     *   - usage: Currently used portion of quota (in bytes)
     *
     * @throws IMP_Exception
     */
    abstract public function getQuota();

    /**
     * Returns the quota messages variants, including sprintf placeholders.
     *
     * @return array  An array with quota message templates.
     */
    public function getMessages()
    {
        return $this->_params['format'];
    }

    /**
     * Determine the units of storage to display in the quota message.
     *
     * @return array  An array of size and unit type.
     */
    public function getUnit()
    {
        $unit = $this->_params['unit'];

        switch ($unit) {
        case 'GB':
            $calc = 1024 * 1024 * 1024.0;
            break;

        case 'KB':
            $calc = 1024.0;
            break;

        case 'MB':
        default:
            $calc = 1024 * 1024.0;
            $unit = 'MB';
            break;
        }

        return array($calc, $unit);
    }

}
