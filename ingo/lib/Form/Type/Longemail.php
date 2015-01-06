<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Do address validation on e-mail forms.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    yann@pleiades.fr.eu.org
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Form_Type_Longemail extends Horde_Form_Type_longtext
{
    /**
     */
    public function isValid(&$var, &$vars, $value, &$message)
    {
        $value = trim($value);

        if (empty($value)) {
            if ($var->isRequired()) {
                $message = _("This field is required.");
                return false;
            }
            return true;
        }

        $invalid = array();
        $rfc822 = $GLOBALS['injector']->getInstance('Horde_Mail_Rfc822');

        foreach (explode("\n", $value) as $address) {
            try {
                $rfc822->parseAddressList($address, array(
                    'validate' => true
                ));
            } catch (Horde_Mail_Exception $e) {
                $invalid[] = $address;
            }
        }

        if (count($invalid)) {
            $message = sprintf(
                ngettext(
                    _("\"%s\" is not a valid email address."),
                    _("\"%s\" are not valid email addresses."),
                    count($invalid)
                ),
                implode(', ', $invalid)
            );
            return false;
        }

        return true;
    }

}
