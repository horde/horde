<?php
/**
 * This is a view of the IMP subinfo bar.
 *
 * Useful properties:
 * - label: (string) Prefix label for the content, e.g. "Mailbox:".
 * - value: (string) The content, e.g. mailbox name.
 * - readonly: (boolean) Whether to add a read-only icon.
 * - quotaText: (string) Text to be added to the quota section. This is done
 *              automatically.
 * - quotaClass: (string) CSS class to be used for the quota section. This is
 *               done automatically.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl21 GPL
 * @package  IMP
 */
class IMP_View_Subinfo extends Horde_View
{
    /**
     * Constructor.
     *
     * @param array $config  Configuration key-value pairs.
     */
    public function __construct($config = array())
    {
        $config['templatePath'] = IMP_TEMPLATES . '/basic';
        parent::__construct($config);

        $quotadata = $GLOBALS['injector']->getInstance('IMP_Ui_Quota')->quota();
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
