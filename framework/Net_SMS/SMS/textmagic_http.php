<?php

/**
 * Net_SMS_textmagic_http Class implements the HTTP API for accessing the
 * TextMagic (api.textmagic.com) SMS gateway.
 *
 * Copyright 2009 Fedyashev Nikita <nikita@realitydrivendeveloper.com>
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @package Net_SMS
 * @author  Fedyashev Nikita <nikita@realitydrivendeveloper.com>
 *
 */
class Net_SMS_textmagic_http extends Net_SMS
{
    var $_base_url = 'https://www.textmagic.com/app/api?';

    function Net_SMS_textmagic_http($params)
    {

        parent::Net_SMS($params);

        if (!extension_loaded('json')) {
            die ("JSON extenstion isn't loaded!");
        }
    }

    /**
     * An array of capabilities, so that the driver can report which operations
     * it supports and which it doesn't. Possible values are:<pre>
     *   send           - Send SMS, scheduled sending;
     *   account        - Check account balance;
     *   messageStatus  - Check messages's cost and delivery status;
     *   receive        - Receive incoming messages;
     *   deleteReply    - Delete specified incoming messages;
     *   checkNumber    - Check phone number validity and destination price;
     * </pre>
     *
     * @var array
     */
    var $capabilities = array('auth'           => false,
                              'batch'          => 100,
                              'multi'          => true,
                              'receive'        => true,
                              'credit'         => true,
                              'addressbook'    => false,
                              'lists'          => false,

                              'message_status' => true,
                              'delete_reply'   => true,
                              'check_number'   => true);

    /**
     * This function does the actual sending of the message.
     *
     * @param array  &$message The array containing the message and its send
     *                         parameters.
     * @param string $to       The destination string.
     *
     * @return array  An array with the success status and additional
     *                information.
     */
    function _send(&$message, $to)
    {

        $unicode    = $this->_getUnicodeParam($message);
        $max_length = $this->_getMaxLengthParam($message);

        $to = implode(',', $to);

        $url = sprintf('cmd=send&phone=%s&text=%s&unicode=%s&max_length=%s',
                       urlencode($to),
                       urlencode($message['text']),
                       $unicode,
                       $max_length);

        $response = $this->_callURL($url);

        if (is_a($response, 'PEAR_Error')) {
              return PEAR::raiseError(sprintf(_("Send failed.")));
        }

        $result = array();

        if (!array_key_exists('error_code', $response)) {

            if (count(explode(',', $to)) == 1) {

                $message_ids = array_keys($response['message_id']);

                $result[$to] = array(
                    0 => 1,
                    1 => $message_ids[0]
                );
            } else {
                foreach ($response['message_id'] as $id => $recipient) {
                    $result[$recipient] = array(1, $id);
                }
            }
        } else {

            if (count(explode(',', $to)) == 1) {

                $result[$to] = array(
                    0 => 0,
                    1 => $response['error_message']
                );
            } else {
                foreach (explode(',', $to) as $recipient) {
                    $result[$recipient] = array(0, $response['error_message']);
                }
            }
        }

        return $result;
    }

    function _getMaxLengthParam($message) {
        $default_params = $this->getDefaultSendParams();

        if (isset($message['send_params']['max_length'])) {
            $max_length = $message['send_params']['max_length'];
        } else {
            $max_length = $default_params['max_length']['default_value'];
        }

        return $max_length;

    }

    function _getUnicodeParam($message) {
        $default_params = $this->getDefaultSendParams();

        if (isset($message['send_params']['unicode'])) {
            $unicode = $message['send_params']['unicode'];
        } else {
            $unicode = $default_params['unicode']['default_value'];
        }

        return $unicode;
    }

    /**
     * This function check message delivery status.
     *
     * @param array $ids The array containing message IDs.
     *
     * @return array  An array with the success status and additional
     *                information.
     */
    function messageStatus($ids)
    {

        if (!is_array($ids)) {
            $ids = array($ids);
        }

        $ids = implode(',', $ids);

        $url = sprintf('cmd=message_status&ids=%s',
                       urlencode($ids));

        $response = $this->_callURL($url);

        if (is_a($response, 'PEAR_Error')) {
              return PEAR::raiseError(sprintf(_("Send failed.")));
        }

        $result = array();

        if (!array_key_exists('error_code', $response)) {

            if (count(explode(',', $ids)) == 1) {

                $result[$ids] = array(
                    0 => 1,
                    1 => $response[$ids]
                );
            } else {
                foreach ($response as $id => $message) {
                    $result[$id] = array(1, $message);
                }
            }
        } else {

            if (count(explode(',', $ids)) == 1) {

                $result[$to] = array(
                    0 => 0,
                    1 => $response['error_message']
                );
            } else {
                foreach (explode(',', $ids) as $id) {
                    $result[$id] = array(0, $response['error_message']);
                }
            }
        }

        return $result;
    }

    /**
     * This function retrieves incoming messages.
     *
     * @param array $last_retrieved_id The array containing message IDs.
     *
     * @return array  An array with the success status and additional
     *                information.
     */
    function receive($last_retrieved_id)
    {
        if (!is_int($last_retrieved_id)) {
            $last_retrieved_id = int($last_retrieved_id);
        }

        $url = sprintf('cmd=receive&last=%s', $last_retrieved_id);

        $response = $this->_callURL($url);

        if (is_a($response, 'PEAR_Error')) {
              return PEAR::raiseError(sprintf(_("Send failed.")));
        }

        $result = array();

        if (!array_key_exists('error_code', $response)) {
            $result[0] = 1;

            $result[1] = $response;
        } else {
            $result[0] = 0;

            $result[1] = $response['error_message'];
        }

        return $result;
    }


    /**
     * This function allows you to delete Incoming message
     *
     * @param array $ids  The array containing message IDs.
     *
     * @return array An array with the success status and additional
     *                information.
     */
    function deleteReply($ids)
    {

        if (!is_array($ids)) {
            $ids = array($ids);
        }

        $ids = implode(',', $ids);

        /* Set up the http sending url. */
        $url = sprintf('cmd=delete_reply&ids=%s',
                       urlencode($ids));

        $response = $this->_callURL($url);

        if (is_a($response, 'PEAR_Error')) {
              return PEAR::raiseError(sprintf(_("Send failed.")));
        }

        $result = array();

        if (!array_key_exists('error_code', $response)) {

            if (count(explode(',', $ids)) == 1) {

                $result[$ids] = array(
                    0 => 1,
                    1 => true
                );
            } else {
                foreach ($response['deleted'] as $id) {
                    $result[$id] = array(1, true);
                }
            }
        } else {

            if (count(explode(',', $ids)) == 1) {

                $result[$to] = array(
                    0 => 0,
                    1 => $response['error_message']
                );
            } else {
                foreach (explode(',', $ids) as $id) {
                    $result[$id] = array(0, $response['error_message']);
                }
            }
        }

        return $result;
    }

     /**
     * This function allows you to validate phone number's format,
     * check its country and message price to the destination .
     *
     * @param array $numbers Phone numbers array to be checked.
     *
     * @return array  An array with the success status and additional
     *                information.
     */
    function checkNumber($numbers)
    {

        if (!is_array($numbers)) {
            $numbers = array($numbers);
        }

        $numbers = implode(',', $numbers);

        $url = sprintf('cmd=check_number&phone=%s',
                       urlencode($numbers));

        $response = $this->_callURL($url);

        if (is_a($response, 'PEAR_Error')) {
              return PEAR::raiseError(sprintf(_("Send failed.")));
        }

        if (is_a($response, 'PEAR_Error')) {
              return PEAR::raiseError(sprintf(_("Send failed.")));
        }

        $result = array();

        if (!array_key_exists('error_code', $response)) {

            if (count(explode(',', $numbers)) == 1) {

                $result[$numbers] = array(
                    0 => 1,
                    1 => array(
                        "price" => $response[$numbers]['price'],
                        "country" => $response[$numbers]['country']
                    )
                );
            } else {
                foreach (explode(',', $numbers) as $number) {
                    $result[$number] = array(1, array(
                        "price" => $response[$number]['price'],
                        "country" => $response[$number]['country']
                    ));
                }
            }
        } else {

            if (count(explode(',', $numbers)) == 1) {

                $result[explode(',', $numbers)] = array(
                    0 => 0,
                    1 => $response['error_message']
                );
            } else {
                foreach (explode(',', $numbers) as $number) {
                    $result[$number] = array(0, $response['error_message']);
                }
            }
        }

        return $result;

    }

    /**
     * Returns the current credit balance on the gateway.
     *
     * @access private
     *
     * @return integer  The credit balance available on the gateway.
     */
    function _getBalance()
    {
        $url = 'cmd=account';

        $response = $this->_callURL($url);

        if (is_a($response, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Send failed. %s"), $response['error_message']));
        }

        if (!array_key_exists('error_code', $response)) {
            return $response['balance'];
        } else {
            return $this->getError($response['error_message'], _("Could not check balance. %s"));
        }
    }

    /**
     * Identifies this gateway driver and returns a brief description.
     *
     * @return array  Array of driver info.
     */
    function getInfo()
    {
        $info['name'] = _("TextMagic via HTTP");
        $info['desc'] = _("This driver allows sending of messages through the TextMagic (http://api.textmagic.com) gateway, using the HTTP API");

        return $info;
    }

    /**
     * Returns the required parameters for this gateway driver.
     *
     * @return array  Array of required parameters.
     */
    function getParams()
    {
        $params               = array();
        $params['username']   = array('label' => _("Username"), 'type' => 'text');
        $params['password']   = array('label' => _("Password"), 'type' => 'text');
        $params['unicode']    = array('label' => _("Unicode message flag"), 'type' => 'int');
        $params['max_length'] = array('label' => _("Maximum messages to be sent at once"), 'type' => 'int');

        return $params;
    }

    /**
     * Returns the parameters that can be set as default for sending messages
     * using this gateway driver and displayed when sending messages.
     *
     * @return array  Array of parameters that can be set as default.
     * @todo  Set up batch fields/params, would be nice to have ringtone/logo
     *        support too, queue choice, unicode choice.
     */
    function getDefaultSendParams()
    {
        $params = array();

        $params['max_length'] = array(
            'label' => _("Max messages quantity"),
            'type' => 'int',
            'default_value' => 3);

        $params['unicode'] = array(
            'label' => _("Unicode message flag"),
            'type' => 'int',
            'default_value' => 1);

        return $params;
    }

    /**
     * Returns the parameters for sending messages using this gateway driver,
     * displayed when sending messages. These are filtered out using the
     * default values set for the gateway.
     *
     * @return array  Array of required parameters.
     */
    function getSendParams($params)
    {
        if (empty($params['max_length'])) {
            $params['max_length'] = array(
                'label' => _("Max messages quantity"),
                'type' => 'int');
        }

        if (empty($params['unicode'])) {
            $params['unicode'] = array(
                'label' => _("Unicode message flag"),
                'type' => 'int');
        }

        return $params;
    }

    /**
     * Returns a string representation of an error code.
     *
     * @param string  $error_text An existing error text to use to raise a
     *                            PEAR Error.
     * @param integer $error The error code to look up.
     *
     * @return mixed  A textual message corresponding to the error code or a
     *                PEAR Error if passed an existing error text.
     *
     * @todo  Check which of these are actually required and trim down the
     *        list.
     */
    function getError($error_text = '', $error)
    {
        /* An array of error codes returned by the gateway. */
        $errors = array(2  => _("Low balance"),
                        5  => _("Invalid username/password combination"),
                        6  => _("Message was not sent"),
                        7  => _("Too long message length"),
                        8  => _("IP address is not allowed"),
                        9  => _("Wrong phone number format"),
                        10 => _("Wrong parameter value"),
                        11 => _("Daily API requests limit exceeded"),
                        12 => _("Too many items per request"),
                        13 => _("Your account has been deactivated"),
                        14 => _("Unknwon message ID"),
                        15 => _("Unicode characters detected on unicode=0 option"));

        if (!empty($error_text)) {
            return $error_text;
        } else {
            return PEAR::raiseError($errors[$error], $error);
        }
    }

    /**
     * Do the http call using a url passed to the function.
     *
     * @param string $url The url to call.
     *
     * @return mixed  The response on success or PEAR Error on failure.
     */
    function _callURL($url)
    {
        $options['method']         = 'POST';
        $options['timeout']        = 5;
        $options['allowRedirects'] = true;

        if (!@include_once 'HTTP/Request.php') {
            return PEAR::raiseError(_("Missing PEAR package HTTP_Request."));
        }
        $http = &new HTTP_Request($this->_base_url . $url, $options);

        /* Add the authentication values to POST. */
        $http->addPostData('username', $this->_params['user']);
        $http->addPostData('password', $this->_params['password']);


        @$http->sendRequest();
        if ($http->getResponseCode() != 200) {
            return PEAR::raiseError(sprintf(_("Could not open %s."), $url));
        }

        return json_decode($http->getResponseBody(), true);
    }

}
