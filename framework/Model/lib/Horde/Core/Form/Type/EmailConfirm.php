<?php
/**
 * Email with confirmation
 */
class Horde_Core_Form_Type_EmailConfirm extends Horde_Core_Form_Type
{
    public function isValid($var, $vars, $value, &$message)
    {
        if ($var->required && empty($value['original'])) {
            $message = Horde_Model_Translation::t("This field is required.");
            return false;
        }

        if ($value['original'] != $value['confirm']) {
            $message = Horde_Model_Translation::t("Email addresses must match.");
            return false;
        }

        $rfc822 = new Horde_Mail_Rfc822();
        $addr_ob = $rfc822->parseAddressList($value['original']);

        switch (count($addr_ob)) {
        case 0:
            $message = Horde_Model_Translation::t("You did not enter a valid email address.");
            return false;

        case 1:
            break;

        default:
            $message = Horde_Model_Translation::t("Only one email address allowed.");
            return false;
        }

        return true;
    }
}
