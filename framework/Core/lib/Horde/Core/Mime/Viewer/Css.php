<?php
/**
 * The Horde_Core_Mime_Viewer_Css class extends the base CSS driver by adding
 * the necessary stylesheets to the full rendered version.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Mime_Viewer_Css extends Horde_Mime_Viewer_Css
{
    /**
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * 'registry' - (Horde_Registry) Registry object.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        $this->_required = array_merge($this->_required, array(
            'registry'
        ));

        parent::__construct($part, $conf);
    }

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        $ret = $this->_renderInline();

        // Need Horde headers for CSS tags.
        if (!empty($ret)) {
            $templates = $this->getConfigParam('registry')->get('templates', 'horde');

            reset($ret);
            Horde::startBuffer();
            require $templates . '/common-header.inc';
            echo $ret[key($ret)]['data'];
            require $templates . '/common-footer.inc';
            $ret[key($ret)]['data'] = Horde::endBuffer();
        }

        return $ret;
    }

}
