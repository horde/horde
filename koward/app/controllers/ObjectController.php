<?php
/**
 * @package Koward
 */

// @TODO Clean up
require_once dirname(__FILE__) . '/ApplicationController.php';

/**
 * @package Koward
 */
class ObjectController extends Koward_ApplicationController
{

    var $object_type;
    var $objectlist;
    var $attributes;
    var $tabs;
    var $object;
    var $post;

    public function listall()
    {
        require_once 'Horde/UI/Tabs.php';
        require_once 'Horde/Variables.php';
        require_once 'Horde/Util.php';

        $this->object_type = $this->params->get('id', $this->types[0]);

        if (isset($this->koward->objects[$this->object_type]['list_attributes'])) {
            $this->attributes = $this->koward->objects[$this->object_type]['list_attributes'];
        } else if (isset($this->koward->objects[$this->object_type]['attributes']['fields'])) {
            $this->attributes = $this->koward->objects[$this->object_type]['attributes']['fields'];
        } else {
            $this->koward->notification->push(sprintf('No attributes have been defined for the list view of objects with type %s.',
                                                      $this->object_type),
                                              'horde.error');
        }

        if (isset($this->attributes)
            && isset($this->koward->objects[$this->object_type])) {
            $params = array('attributes' => array_keys($this->attributes));
            $class = $this->koward->objects[$this->object_type]['class'];
            $this->objectlist = $this->koward->server->listHash($class,
                                                                $params);
            foreach ($this->objectlist as $uid => $info) {
                $this->objectlist[$uid]['edit_url'] = Horde::link(
                    $this->urlFor(array('controller' => 'object', 
                                        'action' => 'edit',
                                        'id' => $uid)),
                    _("Edit")) . Horde::img('edit.png', _("Edit"), '',
                                            $GLOBALS['registry']->getImageDir('horde'))
                    . '</a>';
                $this->objectlist[$uid]['delete_url'] = Horde::link(
                    $this->urlFor(array('controller' => 'object', 
                                        'action' => 'delete',
                                        'id' => $uid)),
                    _("Delete")) . Horde::img('delete.png', _("Delete"), '',
                                              $GLOBALS['registry']->getImageDir('horde'))
                    . '</a>';
                $this->objectlist[$uid]['view_url'] = Horde::link(
                    $this->urlFor(array('controller' => 'object', 
                                        'action' => 'view',
                                        'id' => $uid)), _("View"));
            }
        }

        $this->tabs = new Horde_UI_Tabs(null, Variables::getDefaultVariables());
        foreach ($this->koward->objects as $key => $configuration) {
            $this->tabs->addTab($configuration['list_label'],
                                $this->urlFor(array('controller' => 'object', 
                                                    'action' => 'listall',
                                                    'id' => $key)),
                                $key);
        }

        $this->render();
    }

    public function delete()
    {
        try {
            if (empty($this->params->id)) {
                $this->koward->notification->push(_("The object that should be deleted has not been specified."),
                                                 'horde.error');
            } else {
                $this->object = $this->koward->getObject($this->params->id);
                if (empty($this->params->submit)) {
                    $this->token  = $this->koward->getRequestToken('object.delete');
                } else {
                    $this->koward->checkRequestToken('object.delete', $this->params->token);
                    $this->object->delete();
                    $this->koward->notification->push(sprintf(_("Successfully deleted the object \"%s\""),
                                                             $this->params->id),
                                                     'horde.message');
                    header('Location: ' . $this->urlFor(array('controller' => 'object', 
                                                              'action' => 'listall',
                                                              'id' => get_class($this->object))));
                    exit;
                }
            }
        } catch (Exception $e) {
            $this->koward->notification->push($e->getMessage(), 'horde.error');
        }

        $this->render();
    }

    public function view()
    {
        try {
            if (empty($this->params->id)) {
                $this->koward->notification->push(_("The object that should be viewed has not been specified."),
                                                 'horde.error');
            } else {
                $this->object = $this->koward->getObject($this->params->id);

                require_once 'Horde/Variables.php';
                $this->vars = Variables::getDefaultVariables();
                $this->form = new Koward_Form_Object($this->vars, $this->object,
                                                    array('title' => _("View object")));
            }
        } catch (Exception $e) {
            $this->koward->notification->push($e->getMessage(), 'horde.error');
        }

        $this->render();
    }

    public function edit()
    {
        try {
            if (empty($this->params->id)) {
                $this->object = null;
            } else {
                $this->object = $this->koward->getObject($this->params->id);
            }

            require_once 'Horde/Variables.php';
            $this->vars = Variables::getDefaultVariables();
            $this->form = new Koward_Form_Object($this->vars, $this->object);

            if ($this->form->validate()) {
                $object = $this->form->execute();

                if (!empty($object)) {
                    header('Location: ' . $this->urlFor(array('controller' => 'object', 
                                                              'action' => 'view',
                                                              'id' => $object->get(Horde_Kolab_Server_Object::ATTRIBUTE_UID))));
                    exit;
                }
            }
        } catch (Exception $e) {
            $this->koward->notification->push($e->getMessage(), 'horde.error');
        }

        $this->post = $this->urlFor(array('controller' => 'object', 
                                          'action' => 'edit',
                                          'id' => $this->params->id));

        $this->render();
    }
}