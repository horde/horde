<?php
/**
 * Provides the object that contains the status data to output when viewing
 * MIME parts in IMP.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mime_Status
{
    /* Action constants. */
    const ERROR = 1;
    const SUCCESS = 2;
    const WARNING = 3;

    /**
     * DOM ID to use for the status block.
     *
     * @var string
     */
    protected $_domid;

    /**
     * Icon image HTML.
     *
     * @var string
     */
    protected $_icon;

    /**
     * List of text to output. Each entry will be output on a newline.
     *
     * @var array
     */
    protected $_text = array();

    /**
     * Constructor.
     *
     * @param mixed $text  See addText().
     */
    public function __construct($text = null)
    {
        if (!is_null($text)) {
            $this->addText($text);
        }
    }

    /**
     * Pre-defined actions.
     *
     * @param integer $type  The action type.
     */
    public function action($type)
    {
        switch ($type) {
        case self::ERROR:
            $this->icon('alerts/error.png', _("Error"));
            break;

        case self::SUCCESS:
            $this->icon('alerts/success.png', _("Success"));
            break;

        case self::WARNING:
            $this->icon('alerts/warning.png', _("Warning"));
            break;
        }
    }

    /**
     * Adds text line(s) to the output.
     *
     * @param mixed $text  Either a line of text or an array of lines to add.
     */
    public function addText($text)
    {
        if (!is_array($text)) {
            $text = array($text);
        }

        $this->_text = array_merge($this->_text, $text);
    }

    /**
     * Set the icon to use for the status block (Default: no icon).
     *
     * @param string $img  The image file.
     * @param string $alt  ALT text to use.
     */
    public function icon($img, $alt = null)
    {
        $this->_icon = Horde::img($img, $alt);
    }

    /**
     * Set a DOM ID for the status block.
     *
     * @param string $id  The DOM ID to use.
     */
    public function domid($id = null)
    {
        $this->_domid = $id;
    }

    /**
     * Output status block HTML.
     *
     * @return string  The formatted status message HTML.
     */
    public function __toString()
    {
        $out = '<div><table class="mimeStatusMessageTable"' .
            (isset($this->_domid) ? (' id="' . $this->_domid . '" ') : '')
            . '>';

        /* If no image, simply print out the message. */
        if (empty($this->_icon)) {
            foreach ($this->_text as $val) {
                $out .= '<tr><td>' . $val . '</td></tr>';
            }
        } else {
            $out .= '<tr><td class="mimeStatusIcon">' . $this->_icon . '</td><td><table>';
            foreach ($this->_text as $val) {
                $out .= '<tr><td>' . $val . '</td></tr>';
            }
            $out .= '</table></td></tr>';
        }

        $out .= '</table></div>';

        return '<div class="mimeStatusMessage">' . $out . '</div>';
    }

}
