<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl21 GPL
 * @package   IMP
 */

/**
 * This is a view of the IMP subinfo bar.
 *
 * Useful properties:
 *   - label: (string) Prefix label for the content, e.g. "Mailbox:".
 *   - quotaClass: (string) CSS class to be used for the quota section. This is
 *               done automatically.
 *   - quotaText: (string) Text to be added to the quota section. This is done
 *                automatically.
 *   - readonly: (boolean) Whether to add a read-only icon.
 *   - value: (string) The content, e.g. mailbox name.
 *
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_View_Subinfo extends Horde_View
{
    /**
     * Constructor.
     *
     * @param array $config  Configuration key-value pairs. Additional options:
     *   - mailbox: (string) Mailbox to use for quota query.
     */
    public function __construct(array $config = array())
    {
        $config['templatePath'] = IMP_TEMPLATES . '/basic';
        parent::__construct($config);

        $quotadata = $GLOBALS['injector']->getInstance('IMP_Quota_Ui')->quota(isset($config['mailbox']) ? $config['mailbox'] : null, true);
        if (!empty($quotadata)) {
            $this->quotaClass = $quotadata['class'];
            $this->quotaText = $quotadata['message'];
        }
    }

    /**
     * Returns the HTML code for the subinfo bar.
     *
     * @param string $name  The template to process.
     *
     * @return string  The subinfo bar's HTML code.
     */
    public function render($name = 'subinfo', $locals = array())
    {
        return parent::render($name, $locals);
    }
}
