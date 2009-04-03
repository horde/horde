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

    public function listall()
    {
        require_once 'Horde/UI/Tabs.php';
        require_once 'Horde/Variables.php';
        require_once 'Horde/Util.php';

        $this->object_type = $this->params->get('id', $this->types[0]);

        if (isset($this->koward->objects[$this->object_type])) {
            $this->attributes = $this->koward->objects[$this->object_type]['attributes'];
            $params = array('attributes' => array_keys($this->attributes));
            $this->objectlist = $this->koward->server->listHash($this->object_type,
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
        } else {
            $this->objectlist = 'Unkown object type.';
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

                header('Location: ' . $this->urlFor(array('controller' => 'object', 
                                                          'action' => 'view',
                                                          'id' => $object->get(Horde_Koward_Server_Object::ATTRIBUTE_UID))));
                exit;
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