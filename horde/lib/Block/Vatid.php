<?php
/**
 */
class Horde_Block_Vatid extends Horde_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->enabled = class_exists('SOAP_Client');
    }

    /**
     */
    public function getName()
    {
        return _("EU VAT identification");
    }

    /**
     */
    protected function _title()
    {
        return _("VAT id number verification");
    }

    /**
     */
    protected function _content()
    {
        $name = strval(new Horde_Support_Randomid());

        Horde::addScriptFile('vatid.js', 'horde');
        Horde::addInlineScript(
            '$("' . $name . '").observe("submit", HordeBlockVatid.onSubmit.bindAsEventListener(HordeBlockVatid))',
            'dom'
        );

        return '<form style="padding:2px" action="' .
            $this->_ajaxUpdateUrl() . '" id="' . $name . '">' .
            Horde_Util::formInput() .
            Horde::label('vatid', _("VAT identification number:")) .
            '<br /><input type="text" length="14" name="vatid" />' .
            '<br /><input type="submit" id="vatbutton" value="' . _("Check") .
            '" class="button" /> ' .
            Horde::img('loading.gif', _("Checking"), array('style' => 'display:none')) .
            '<div class="vatidResults"></div>' .
            '</form>';
    }

    /**
     */
    protected function _ajaxUpdate($vars)
    {
        $html = '';
        $vatid = str_replace(' ', '', $vars->vatid);

        if (empty($vatid) ||
            !preg_match('/^([A-Z]{2})([0-9A-Za-z\+\*\.]{2,12})$/', $vatid, $matches)) {
            return '<br />' . $this->_error(_("Invalid VAT identification number format."));
        }

        if (empty($matches)) {
            return;
        }

        $client = new SOAP_Client('http://ec.europa.eu/taxation_customs/vies/api/checkVatPort?wsdl', true, false, array(), Horde::getTempDir());
        $params = array(
            'countryCode' => $matches[1],
            'vatNumber' => $matches[2]
        );
        $result = $client->call('checkVat', $params);
        if ($result instanceof SOAP_Fault) {
            $error = $result->getMessage();

            switch (true) {
            case strpos($error, 'INVALID_INPUT'):
                $error = _("The provided country code is invalid.");
                break;

            case strpos($error, 'SERVICE_UNAVAILABLE'):
                $error = _("The service is currently not available. Try again later.");
                break;

            case strpos($error, 'MS_UNAVAILABLE'):
                $error = _("The member state service is currently not available. Try again later or with a different member state.");
                break;

            case strpos($error, 'TIMEOUT'):
                $error = _("The member state service could not be reached in time. Try again later or with a different member state.");
                break;

            case strpos($error, 'SERVER_BUSY'):
                $error = _("The service is currently too busy. Try again later.");
                break;
            }

            $html .= $this->_error($error);
        } else {
            if ($result['valid']) {
                $html .= '<span style="color:green;font-weight:bold">'
                    . _("This VAT identification number is valid.")
                    . '</span><br />';
            } else {
                $html .= $this->_error(_("This VAT identification number is invalid.")) . '<br />';
            }

            $html .= '<em>' . _("Country") . ':</em> '
                . $result['countryCode'] . '<br /><em>'
                . _("VAT number") . ':</em> ' . $result['vatNumber']
                . '<br /><em>' . _("Date") . ':</em> '
                . strftime($GLOBALS['prefs']->getValue('date_format'), strtotime($result['requestDate']))
                . '<br />';

            if (!empty($result['name'])) {
                $html .= '<em>' . _("Name") . ':</em> ' . $result['name'] . '<br />';
            }

            if (!empty($result['address'])) {
                $html .= '<em>' . _("Address") . ':</em> ' . $result['address'] . '<br />';
            }
        }

        return $html;
    }

    /**
     */
    private function _error($text)
    {
        return '<span style="color:red;font-weight:bold">' . $text . '</span>';
    }

}
