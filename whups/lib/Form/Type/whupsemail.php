<?php
/**
 * @package Horde_Form
 */
class Whups_Form_Type_whupsemail extends Horde_Form_Type_email
{

    /**
     * @param string $email An individual email address to validate.
     *
     * @return boolean
     */
    function validateEmailAddress($email)
    {
        $rfc = new Horde_Mail_Rfc822();
        try {
            $rfc->parseAddressList($email, array('validate' => true));
        } catch (Horde_Mail_Exception $e) {
            return false;
        }

        return true;
    }

}