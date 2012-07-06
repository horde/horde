<?php
/**
 * Imple to allow in-place editing of a HTML element.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
abstract class Horde_Core_Ajax_Imple_InPlaceEditor extends Horde_Core_Ajax_Imple
{
    /**
     * @param array $params  Configuration parameters:
     *   - cols: (integer) Number of columns.
     *   - dataid: (string) Data ID passed to the handler.
     *   - rows: (integer) Number of rows.
     */
    public function __construct(array $params = array())
    {
        /* Set up some defaults */
        $params = array_merge(array(
            'cols' => 20,
            'dataid' => '',
            'rows' => 2
        ), $params);

        parent::__construct($params);
    }

    /**
     */
    protected function _attach($init)
    {
        global $page_output;

        if ($init) {
            $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
            $page_output->addScriptFile('inplaceeditor.js', 'horde');


            $value_url = $this->getImpleUrl()->add(array(
                'id' => $this->_params['dataid'],
                'input' => 'value'
            ))->setRaw(true);
            $load_url = $value_url->copy()->add(array(
                'action' => 'load'
            ))->setRaw(true);
            $config = new stdClass;
            $config->config = array(
                'cancelClassName' => '',
                'cancelText' => _("Cancel"),
                'emptyText' => _("Click to add caption..."),
                'okText' => _("Ok")
            );
            $config->ids = new stdClass;
            $config->ids->{$this->getDomId()} = array(
                'load_url' => (string)$load_url,
                'rows' => $this->_params['rows'],
                'value_url' => (string)$value_url
            );

            if (!empty($this->_params['width'])) {
                $config->ids->{$this->getDomId()}['width'] = $this->_params['width'];
            }

            $page_output->addInlineJsVars(array(
                'HordeImple.InPlaceEditor' => $config
            ));
            $page_output->addInlineScript(array(
                '$H(HordeImple.InPlaceEditor.ids).each(function(pair) {
                     new InPlaceEditor(pair.key, pair.value.value_url, Object.extend(HordeImple.InPlaceEditor.config, {
                         htmlResponse: false,
                         callback: function(form, value) {
                             return "value=" + encodeURIComponent(value);
                         },
                         onComplete: function(ipe, opts) {
                            $("' . $this->getDomId() . '").update(opts.responseJSON)
                             ipe.checkEmpty()
                         },
                         loadTextURL: pair.value.load_url,
                         rows: pair.value.rows,
                         autoWidth: true
                     }));
                 })'
            ), true);
        }

        return false;
    }

    /**
     */
    protected function _handle(Horde_Variables $vars)
    {
        $data = (!$vars->load && (!isset($vars->input) || !isset($vars->id)))
            ? ''
            : $this->_handleEdit($vars);

        return new Horde_Core_Ajax_Response_Prototypejs($data);
    }

    /**
     * @return mixed  Raw data to return to in-place-editor.
     */
    abstract protected function _handleEdit(Horde_Variables $vars);

}
