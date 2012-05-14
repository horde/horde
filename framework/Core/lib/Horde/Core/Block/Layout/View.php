<?php
/**
 * This object represents the user defined portal layout.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Block_Layout_View extends Horde_Core_Block_Layout
{
    /**
     * All applications used in this layout.
     *
     * @var array
     */
    protected $_applications = array();

    /**
     * The current block layout.
     *
     * @var array
     */
    protected $_layout = array();

    /**
     * Constructor.
     *
     * @param array $layout
     * @param Horde_Url $editUrl
     * @param Horde_Url $viewUrl
     */
    public function __construct($layout = array(), $editUrl, $viewUrl)
    {
        $this->_layout = $layout;
        $this->_editUrl = $editUrl;
        $this->_viewUrl = $viewUrl;
    }

    /**
     * Render the current layout as HTML.
     *
     * @return string  HTML layout.
     */
    public function toHtml()
    {
        $tplDir = $GLOBALS['registry']->get('templates', 'horde');
        $interval = $GLOBALS['prefs']->getValue('summary_refresh_time');

        $html = '<table id="portal" class="nopadding" cellspacing="8" width="100%">';

        $bc = $GLOBALS['injector']->getInstance('Horde_Core_Factory_BlockCollection')->create();
        $covered = array();

        foreach ($this->_layout as $row_num => $row) {
            $width = floor(100 / count($row));
            $html .= '<tr>';

            foreach ($row as $col_num => $item) {
                if (isset($covered[$row_num]) &&
                    isset($covered[$row_num][$col_num])) {
                    continue;
                }

                if (is_array($item)) {
                    $block_id = 'block_' . $row_num . '_' . $col_num;
                    $this->_applications[$item['app']] = $item['app'];
                    $rowspan = $colspan = 1;
                    try {
                        $block = $bc->getBlock($item['app'], $item['params']['type2'], $item['params']['params']);
                        $rowspan = $item['height'];
                        $colspan = $item['width'];
                        for ($i = 0; $i < $item['height']; $i++) {
                            if (!isset($covered[$row_num + $i])) {
                                $covered[$row_num + $i] = array();
                            }
                            for ($j = 0; $j < $item['width']; $j++) {
                                $covered[$row_num + $i][$col_num + $j] = true;
                            }
                        }

                        if ($block instanceof Horde_Core_Block) {
                            $content = $block->getContent();
                            $header = $block->getTitle();

                            ob_start();
                            include $tplDir . '/portal/block.inc';
                            $html .= ob_get_clean();

                            if ($block->updateable &&
                                $GLOBALS['browser']->hasFeature('xmlhttpreq')) {
                                $refresh_time = isset($item['params']['params']['_refresh_time'])
                                    ? $item['params']['params']['_refresh_time']
                                    : $interval;

                                if (!empty($refresh_time)) {
                                    $updateurl = $GLOBALS['registry']->getServiceLink('ajax')->setRaw(true);
                                    $updateurl->pathInfo = 'blockAutoUpdate';
                                    $updateurl->add('app', $block->getApp())
                                              ->add('blockid', get_class($block));

                                    $GLOBALS['injector']->getInstance('Horde_PageOutput')->addInlineScript(
                                        'setTimeout(function() {' .
                                          'new Ajax.PeriodicalUpdater(' .
                                            '"' . $block_id . '",' .
                                            '"' . strval($updateurl) . '",' .
                                            '{ method: "get", evalScripts: true, frequency: ' . intval($refresh_time) . ' }' .
                                          ');' .
                                        '}, ' . intval($refresh_time * 1000) . ')',
                                        true
                                    );
                                }
                            }
                        } else {
                            $html .= '<td width="' . ($width * $colspan) . '%">&nbsp;</td>';
                        }
                    } catch (Horde_Exception $e) {
                        $header = Horde_Core_Translation::t("Error");
                        $content = $e->getMessage();
                        ob_start();
                        include $tplDir . '/portal/block.inc';
                        $html .= ob_get_clean();
                    }
                } else {
                    $html .= '<td width="' . ($width) . '%">&nbsp;</td>';
                }
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        return $html;
    }

    /**
     * Return a list of all the applications used by blocks in this layout.
     *
     * @return array  List of applications.
     */
    public function getApplications()
    {
        return array_keys($this->_applications);
    }

    /**
     * @return array  List of stylesheet information.
     */
    public function getStylesheets()
    {
        $css = $GLOBALS['injector']->getInstance('Horde_PageOutput')->css;
        $stylesheets = array();

        foreach ($this->getApplications() as $app) {
            $app_css = $css->getStylesheets('', array(
                'app' => $app,
                'nohorde' => true,
                'sub' => 'block',
                'subonly' => true
            ));

            // TODO: BC - fallback to loading full app stylesheets if the
            // 'block' subdirectory is not found.
            if (empty($app_css)) {
                $app_css = $css->getStylesheets('', array(
                    'app' => $app,
                    'nohorde' => true
                ));
            }

            $stylesheets = array_merge($stylesheets, $app_css);
        }

        return $stylesheets;
    }

}
