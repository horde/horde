<?php
/**
 * $Horde: shout/lib/Dialplan.php,v 0.1 2005/07/16 11:06:48 ben Exp $
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @package Shout
 */
// {{{
class ExtensionDetailsForm extends Horde_Form {

    function ExtensionDetailsForm(&$vars)
    {
        global $shout;
        $context = $vars->get("context");
        $extension = $vars->get("extension");
        
        $dialplan = &$shout->getDialplan($context);
        $extendata = $dialplan['extensions'][$extension];
        if (array_key_exists($extension, $dialplan['extensions'])) {
            $formtitle = "Edit Extension";
        } else {
            $formtitle = "Add Extension";
        }
       
        parent::Horde_Form($vars, _("$formtitle - Context: $context"));
        
        $this->addHidden('', 'context', 'text', true);
        $this->addHidden('', 'oldextension', 'text', true);
        $vars->set('oldextension', $extension);
        $this->addHidden('', 'action', 'text', true);
//         $vars->set('action', 'save');
        $this->addVariable(_("Extension"), 'extension', 'text', true);
        $this->addVariable(_("Priority"), 'priority', 'priority', true);
//         foreach ($extendata as $priority => $application) {
//             $vars->set("priority$priority", $application);
//             $this->addVariable("Priority $priority", "priority$priority",
//                 'text', false);
//         }
    }
    
    // {{{ fillUserForm method
    /**
     * Fill in the blanks for the UserDetailsForm
     *
     * @param object Reference to a Variables object to fill in
     *
     * @param array User details
     *
     * @return boolean True if successful, Pear::raiseError object on failure
     */
    function fillExtensionFormPriority(&$vars, $extensiondetails)
    {
    #Array ( [dialopts] => Array ( [0] => m [1] => t ) [mailboxopts] => Array (
    #) [mailboxpin] => 1234 [name] => Ricardo Paul [phonenumbers] => Array ( )
    #[dialtimeout] => 30 [email] => ricardo.paul@v-office.biz [pageremail] => ) 
        $vars->set('name', $userdetails['name']);
        $vars->set('email', @$userdetails['email']);
        $vars->set('pin', $userdetails['mailboxpin']);
        
        $i = 1;
        foreach($userdetails['phonenumbers'] as $number) {
            $vars->set("telephone$i", $number);
            $i++;
        }
        
        if (in_array('m', $userdetails['dialopts'])) {
            $vars->set('moh', true);
        } else {
            $vars->set('moh', false);
        }
        
        if (in_array('t', $userdetails['dialopts'])) {
            $vars->set('transfer', true);
        } else {
            $vars->set('transfer', false);
        }
        
        return true;
    }
    // }}}
}
// }}}

class Horde_Form_Type_priority extends Horde_Form_Type {

//     var $_regex;
//     var $_size;
//     var $_maxlength;

    /**
     * The initialisation function for the text variable type.
     *
     * @access private
     *
     * @param string $regex       Any valid PHP PCRE pattern syntax that
     *                            needs to be matched for the field to be
     *                            considered valid. If left empty validity
     *                            will be checked only for required fields
     *                            whether they are empty or not.
     *                            If using this regex test it is advisable
     *                            to enter a description for this field to
     *                            warn the user what is expected, as the
     *                            generated error message is quite generic
     *                            and will not give any indication where
     *                            the regex failed.
     * @param integer $size       The size of the input field.
     * @param integer $maxlength  The max number of characters.
     */
     function init()
     {
     }
//     function init($regex = '', $size = 40, $maxlength = null)
//     {
//         $this->_regex     = $regex;
//         $this->_size      = $size;
//         $this->_maxlength = $maxlength;
//     }

    function isValid(&$var, &$vars, $value, &$message)
    {
        $valid = true;

//         if ($var->isRequired() && empty($this->_regex)) {
//             $valid = strlen(trim($value)) > 0;
// 
//             if (!$valid) {
//                 $message = _("This field is required.");
//             }
//         } elseif (!empty($this->_regex)) {
//             $valid = preg_match($this->_regex, $value);
// 
//             if (!$valid) {
//                 $message = _("You have to enter a valid value.");
//             }
//         }

        return $valid;
    }

    function getSize()
    {
        return $this->_size;
    }

    function getMaxLength()
    {
        return $this->_maxlength;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        $about = array();
        $about['name'] = _("Extension Priority");
        $about['params'] = array(
            'priority'  => array('label' => _("Priority"),
                                 'type'  => 'int'),
            'application'      => array('label' => _("Application"),
                                 'type'  => 'stringlist'),
            'args' => array('label' => _("Arguments"),
                                 'type'  => 'text'),
        );
        return $about;
    }

}

// require_once HORDE_BASE . '/lib/Horde/UI/VarRenderer.php';
// require_once HORDE_BASE . '/lib/Horde/UI/VarRenderer/html.php';
// class Horde_UI_VarRenderer_html_priority extends Horde_UI_VarRenderer_html
// {
//     function _renderVarInput_priority(&$form, &$var, &$vars)
//     {
//         echo '<input type="text" name="priority[0]" value="88" size="3" ';
//         echo 'id="priority[0]" />';
//         echo '<select><option>GotoSelf</option></select>\n';
//         echo '<input type="text" name="application[0]" ';
//         echo 'size="40" value="101" id="application[0]" />';
//     }
//     
//     function _renderVarDisplay_priority(&$form, &$var, &$vars)
//     {
//         echo '<input type="text" name="priority[0]" value="88" size="3" ';
//         echo 'id="priority[0]" />';
//         echo '<select><option>GotoSelf</option></select>\n';
//         echo '<input type="text" name="application[0]" ';
//         echo 'size="40" value="101" id="application[0]" />';
//     }
// }