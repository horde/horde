<?php
/**
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @package Horde_Rdo
 */

/**
 * The Horde_Rdo_Table_Helper class provides an Rdo extension to Horde_Template
 * used to generate browsing lists for different backends.
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
    protected $params = array();

    /**
     * Mapper object that we display.
     *
     * @var Horde_Rdo_Mapper $object
     */
    protected $object;

    /**
     * Constructor
     *
     * @param Horde_Rdo_Mapper $object Mapper instance.
     * @param array $params Template defaults.
     */
    public function __construct($params = array(), $object)
    {
        $this->object = $object;
        $params['name'] = $object->model->table;
        $params['id'] = $object->model->table;

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

        $this->params = array_merge($defaults, $params);

        parent::Horde_Template();
        $this->setOption('gettext', true);
    }

    /**
     * Get Rdo members.
     */
    public function getRows()
    {
        $query = $this->prepareQuery();
        if (!empty($this->params['perpage'])) {
            $limit = $this->params['perpage'];
            $offset = $this->params['page'] * $this->params['perpage'];
            $query->limit($limit, $offset);
        }

        $rows = new Horde_Rdo_List($query);

        if (!empty($this->params['decorator'])) {
            $decorator = $this->params['decorator'];
            $rows = new Horde_Lens_Iterator($rows, new $decorator());
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
        return $this->object->count($this->prepareQuery());
    }

    /**
     * Prepares Rdo query.
     */
    private function prepareQuery()
    {
        $query = new Horde_Rdo_Query($this->object);

        if (!empty($this->params['filter'])) {
            foreach ($this->params['filter'] as $key => $val) {
                if (is_array($val)) {
                    $query->addTest($val['field'], $val['test'], $val['value']);
                } else {
                    $query->addTest($key, '=', $val);
                }
            }
        }

        if (!empty($this->params['sort'])) {
            if (!is_array($this->params['sort'])) {
                $this->params['sort'] = array($this->params['sort']);
            }
            foreach ($this->params['sort'] as $sort) {
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
    protected function getFields()
    {
        if ($this->params['columns']) {
            return $this->params['columns'];
        } elseif (!empty($this->params['relationships'])) {
            $fields = $this->object->model->listFields();
            foreach ($this->object->relationships as $r) {
                $mapper_name = $r['mapper'];
                $mapper = new $mapper_name();
                $fields = array_merge($fields, $mapper->model->listFields());
            }
            return $fields;
        } else {
            return $this->object->model->listFields();
        }
    }

    /**
     * Return field names.
     */
    protected function listFields()
    {
        $meta = array();
        if (method_exists($this->object, 'formMeta')) {
            $meta = $this->object->formMeta('table');
            if ($meta instanceof PEAR_Error) {
                return $meta;
            }
        }

        $columns = array();

        $keys = $this->getFields();
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
    protected function getPrimaryKey()
    {
        if (method_exists($this->object, 'getPrimaryKey')) {
            $keys = $this->object->getPrimaryKey();
        } else {
            $keys = array($this->object->model->key);
        }

        if (!$keys) {
            $keys = $this->getFields();
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
            $template = $this->getTemplateFile();
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
            $this->set('img_dir', $this->params['img_dir']);
        }

        if (empty($this->_scalars['url'])) {
            $this->set('url', $this->params['url']);
        }
    }

    /**
     * Get template path if it exists.
     *
     * @return string Template filename.
     */
    protected function getTemplateFile()
    {
        $filename = $GLOBALS['registry']->get('templates') . DIRECTORY_SEPARATOR . $this->params['name'] . '.html';
        if (file_exists($filename)) {
            return $filename;
        }

        $filename = $GLOBALS['registry']->get('templates', 'horde') . DIRECTORY_SEPARATOR . $this->params['name'] . '.html';
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
        $columns = $this->listFields();

        $keys = array();
        $primaryKeys = $this->getPrimaryKey();
        foreach ($primaryKeys as $key) {
            $keys[] = $key . '=<tag:rows.' . $key . ' />';
        }
        $keys = implode('&', $keys);

        $content = '<table id="' . $this->params['id'] . '" class="striped sortable">' . "\n" .
            '<thead>' . "\n" . '<tr>' . "\n";
        if ($this->params['update'] || $this->params['delete']) {
            $content .= '<th class="nosort"><gettext>Actions</gettext></th>' . "\n";
        }
        foreach ($columns as $key => $name) {
            if (in_array($key, $this->params['sort'])) {
                $content .= '<th class="sortdown">' . htmlspecialchars($name) . '</th>' . "\n";
            } else {
                $content .= '<th>' . htmlspecialchars($name) . '</th>' . "\n";
            }
        }
        $content .= '</tr>' . "\n" . '</thead>' . "\n" . '<tbody>' .
            '<loop:rows><tr>' . "\n";

        /* Actions. */
        if ($this->params['update'] || $this->params['delete']) {
            $content .= '<td class="nowrap">' . "\n";
            if ($this->params['update']) {
                $content .= '<a class="update" href="' . $url . '&action=update&' . $keys . '">' .
                    '<img src="<tag:img_dir />/edit.png" alt="<gettext>Edit</gettext>" title="<gettext>Edit</gettext>" /></a> ' . "\n";
            }
            if ($this->params['delete']) {
                $content .= '<a class="delete" href="' . $url . '&action=delete&' . $keys . '">' .
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
