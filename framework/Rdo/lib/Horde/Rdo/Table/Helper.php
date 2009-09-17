<?php
/**
 * The Horde_Rdo_Table_Helper class provides an Rdo extension to Horde_Template
 * used to generate browsing lists for different backends.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @since   Horde 4.0
 * @package Horde_Rdo
 */
class Horde_Rdo_Table_Helper extends Horde_Template
{
    /**
     * Parameters for this Template instance.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Mapper object that we display.
     *
     * @var Horde_Rdo_Mapper $object
     */
    protected $_mapper;

    /**
     * Constructor
     *
     * @param Horde_Rdo_Mapper $object Mapper instance.
     * @param array $params Template defaults.
     */
    public function __construct($params = array(), $object)
    {
        $this->_mapper = $object;

        $params['name'] = $object->table;
        $params['id'] = $object->table;

        $defaults = array(
            'delete' => true,
            'update' => true,
            'columns' => null,
            'name' => '',
            'id' => '',
            'page' => 0,
            'url' => Horde::selfUrl(),
            'img_dir' => $GLOBALS['registry']->getImageDir('horde'),
            'sort' => array(),
            'filter' => array(),
        );

        $this->_params = array_merge($defaults, $params);

        parent::__construct();
        $this->setOption('gettext', true);
    }

    /**
     * Get Rdo members.
     */
    public function getRows()
    {
        $query = $this->_prepareQuery();
        if (!empty($this->_params['perpage'])) {
            $limit = $this->_params['perpage'];
            $offset = $this->_params['page'] * $this->_params['perpage'];
            $query->limit($limit, $offset);
        }

        $rows = new Horde_Rdo_List($query);

        if (!empty($this->_params['decorator'])) {
            $decorator = $this->_params['decorator'];
            // $rows = new Horde_Lens_Iterator($rows, new $decorator());
        }

        return $rows;
    }

    /**
     * Count Rdo members.
     *
     * @param array $filter filter/search rows
     */
    public function count()
    {
        return $this->_mapper->count($this->_prepareQuery());
    }

    /**
     * Prepares Rdo query.
     */
    private function _prepareQuery()
    {
        $query = new Horde_Rdo_Query($this->_mapper);

        if (!empty($this->_params['filter'])) {
            foreach ($this->_params['filter'] as $key => $val) {
                if (is_array($val)) {
                    $query->addTest($val['field'], $val['test'], $val['value']);
                } else {
                    $query->addTest($key, '=', $val);
                }
            }
        }

        if (!empty($this->_params['sort'])) {
            if (!is_array($this->_params['sort'])) {
                $this->_params['sort'] = array($this->_params['sort']);
            }
            foreach ($this->_params['sort'] as $sort) {
                if (is_array($sort)) {
                    $query->sortBy($sort[0], $sort[1]);
                } else {
                    $query->sortBy($sort);
                }
            }
        }

        return $query;
    }

    /**
     * Return field information.
     */
    protected function _getFields()
    {
        if ($this->_params['columns']) {
            return $this->_params['columns'];
        } elseif (!empty($this->_params['relationships'])) {
            $fields = $this->_mapper->fields;
            foreach ($this->_mapper->relationships as $r) {
                $mapper_name = $r['mapper'];
                $mapper = new $mapper_name();
                $fields = array_merge($fields, $mapper->fields);
            }
            return $fields;
        } else {
            return $this->_mapper->fields;
        }
    }

    /**
     * Return field names.
     */
    protected function _listFields()
    {
        $meta = array();
        if (method_exists($this->_mapper, 'formMeta')) {
            $meta = $this->_mapper->formMeta('table');
            if ($meta instanceof PEAR_Error) {
                return $meta;
            }
        }

        $columns = array();

        $keys = $this->_getFields();
        foreach ($keys as $key) {
            if (isset($meta[$key]['humanName'])) {
                $columns[$key] = $meta[$key]['humanName'];
            } elseif ($key == 'created') {
                $columns[$key] = 'Created';
            } elseif ($key == 'updated') {
                $columns[$key] = 'Updated';
            } else {
                $columns[$key] = $key;
            }
        }

        return $columns;
    }

    /**
     * Get primary key.
     */
    protected function _getPrimaryKey()
    {
        if (method_exists($this->_mapper, 'getPrimaryKey')) {
            $keys = $this->_mapper->getPrimaryKey();
        } else {
            $keys = $this->_mapper->tableDefinition->getPrimaryKey()->columns;
        }

        if (empty($keys)) {
            $keys = $this->fields;
        }

        return $keys;
    }

    /**
     * Fetch template.
     *
     * @param string $template Template path.
     */
    public function fetch($template = null)
    {
        if ($template === null) {
            $template = $this->_getTemplateFile();
        }

        if ($template) {
            return parent::fetch($template);
        }

        return parent::parse($this->getTemplate());
    }

    public function __toString()
    {
        return $this->fetch();
    }

    /**
     * Fill up table data.
     */
    public function fill()
    {
        $this->set('rows', $this->getRows());

        if (empty($this->_scalars['img_dir'])) {
            $this->set('img_dir', $this->_params['img_dir']);
        }

        if (empty($this->_scalars['url'])) {
            $this->set('url', $this->_params['url']);
        }
    }

    /**
     * Get template path if it exists.
     *
     * @return string Template filename.
     */
    protected function _getTemplateFile()
    {
        $filename = $GLOBALS['registry']->get('templates') . DIRECTORY_SEPARATOR . $this->_params['name'] . '.html';
        if (file_exists($filename)) {
            return $filename;
        }

        $filename = $GLOBALS['registry']->get('templates', 'horde') . DIRECTORY_SEPARATOR . $this->_params['name'] . '.html';
        if (file_exists($filename)) {
            return $filename;
        }

        return false;
    }

    /**
     * Create template content.
     *
     * @return string $content
     */
    public function getTemplate()
    {
        $url = '<tag:url />';
        $columns = $this->_listFields();

        $keys = array();
        $primaryKeys = $this->_getPrimaryKey();
        foreach ($primaryKeys as $key) {
            $keys[] = $key . '=<tag:rows.' . $key . ' />';
        }
        $keys = implode('&', $keys);

        $content = '<table id="' . $this->_params['id'] . '" class="striped sortable" style="width: 100%;">' . "\n" .
            '<thead>' . "\n" . '<tr>' . "\n";
        if ($this->_params['update'] || $this->_params['delete']) {
            $content .= '<th class="nosort"><gettext>Actions</gettext></th>' . "\n";
        }
        foreach ($columns as $key => $name) {
            if (in_array($key, $this->_params['sort'])) {
                $content .= '<th class="sortdown">' . htmlspecialchars($name) . '</th>' . "\n";
            } else {
                $content .= '<th>' . htmlspecialchars($name) . '</th>' . "\n";
            }
        }
        $content .= '</tr>' . "\n" . '</thead>' . "\n" . '<tbody>' .
            '<loop:rows><tr>' . "\n";

        /* Actions. */
        if ($this->_params['update'] || $this->_params['delete']) {
            $content .= '<td class="nowrap">' . "\n";
            if ($this->_params['update']) {
                $content .= '<a class="update" href="' . Horde_Util::addParameter($url, 'action', 'update') . '&' . $keys . '">' .
                    '<img src="<tag:img_dir />/edit.png" alt="<gettext>Edit</gettext>" title="<gettext>Edit</gettext>" /></a> ' . "\n";
            }
            if ($this->_params['delete']) {
                $content .= '<a class="delete" href="' . Horde_Util::addParameter($url, 'action', 'delete') . '&' . $keys . '">' .
                    '<img src="<tag:img_dir />/delete.png" alt="<gettext>Delete</gettext>" title="<gettext>Delete</gettext>" /></a> ' . "\n";
            }
            $content .= '</td>' . "\n";
        }

        foreach ($columns as $key => $name) {
            $content .= '<td><tag:rows.' . $key . ' /></td>';
        }

        return $content . '</tr>' . "\n" . '</loop:rows>' . "\n" . '</tbody></table>';
    }

}
