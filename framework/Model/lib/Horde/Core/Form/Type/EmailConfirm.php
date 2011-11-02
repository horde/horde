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
        } else {
            try {
                $parsed_email = Horde_Mime_Address::parseAddressList($value['original'], array(
                    'validate' => true
                ));
            } catch (Horde_Mime_Exception $e) {
                $message = $e->getMessage();
                return false;
            }
            if (count($parsed_email) > 1) {
                $message = Horde_Model_Translation::t("Only one email address allowed.");
                return false;
            }
            if (empty($parsed_email[0]->mailbox)) {
                $message = Horde_Model_Translation::t("You did not enter a valid email address.");
                return false;
            }
        }

        return true;
    }
}
