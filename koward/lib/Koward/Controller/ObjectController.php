<?php
/**
 * @package Koward
 */

/**
 * @package Koward
 */
class ObjectController extends Koward_Controller_Application
{

    var $object_type;
    var $objectlist;
    var $attributes;
    var $tabs;
    var $object;
    var $post;

    public function listall()
    {
        $this->object_type = $this->params->get('id', $this->types[0]);

        $this->checkAccess($this->getPermissionId() . '/' . $this->object_type);

        $this->allowEdit = $this->koward->hasAccess('object/edit/' . $this->object_type,
                                                    Koward::PERM_EDIT);
        $this->allowDelete = $this->koward->hasAccess('object/delete/' . $this->object_type,
                                                      Koward::PERM_DELETE);

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
            $this->objectlist = $this->koward->getServer()->listHash($class,
                                                                     $params);
            foreach ($this->objectlist as $uid => $info) {
                $this->objectlist[$uid]['edit_url'] = Horde::link(
                    $this->urlFor(array('controller' => 'object',
                                        'action' => 'edit',
                                        'id' => $uid)),
                    _("Edit")) . Horde::img('edit.png', _("Edit"))
                    . '</a>';
                $this->objectlist[$uid]['delete_url'] = Horde::link(
                    $this->urlFor(array('controller' => 'object',
                                        'action' => 'delete',
                                        'id' => $uid)),
                    _("Delete")) . Horde::img('delete.png', _("Delete"))
                    . '</a>';
                if ($this->koward->hasAccess('object/view/' . $this->object_type, Koward::PERM_READ)) {
                    $this->objectlist[$uid]['view_url'] = Horde::link(
                        $this->urlFor(array('controller' => 'object',
                                            'action' => 'view',
                                            'id' => $uid)), _("View"));
                }
            }
        }

        $this->tabs = new Horde_Core_Ui_Tabs(null, Horde_Variables::getDefaultVariables());
        foreach ($this->koward->objects as $key => $configuration) {
            if (!$this->koward->hasAccess($this->getPermissionId() . '/' . $key)) {
                continue;
            }
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

                $this->checkAccess($this->getPermissionId() . '/' . $this->koward->getType($this->object),
                                   Koward::PERM_DELETE);

                $this->submit_url = $this->urlFor(array('controller' => 'object',
                                                        'action' => 'delete',
                                                        'id' => $this->params->id,
                                                        'token' => Horde::getRequestToken('object.delete')));
                $this->return_url = $this->urlFor(array('controller' => 'object',
                                                        'action' => 'listall'));

                if (!empty($this->params->token)) {
                    if (is_array($this->params->token) && count($this->params->token) == 1) {
                        $token = $this->params->token[0];
                    } else {
                        $token = $this->params->token;
                    }
                    Horde::checkRequestToken('object.delete', $token);
                    $result = $this->object->delete();
                    if ($result === true) {
                        $this->koward->notification->push(sprintf(_("Successfully deleted the object \"%s\""),
                                                                  $this->params->id),
                                                          'horde.message');
                    } else {
                        $this->koward->notification->push(_("Failed to delete the object."),
                                                          'horde.error');
                    }
                    $this->urlFor(array('controller' => 'object',
                                        'action' => 'listall'))
                        ->redirect();
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

                $this->object_type = $this->koward->getType($this->object);

                $this->checkAccess($this->getPermissionId() . '/' . $this->object_type,
                                   Koward::PERM_READ);

                $this->allowEdit = $this->koward->hasAccess('object/edit/' . $this->object_type,
                                                            Koward::PERM_EDIT);

                $buttons = $this->_getButtons($this->object, $this->object_type);
                if (!empty($buttons)) {
                    try {
                        $this->actions = new Koward_Form_Actions($this->object, $buttons);

                        $this->post = $this->urlFor(array('controller' => 'object',
							  'action' => 'view',
							  'id' => $this->params->id));

                        if (!empty($this->params->token) && !empty($this->params->oaction)) {
                            if (is_array($this->params->token) && count($this->params->token) == 1) {
                                $token = $this->params->token[0];
                            } else {
                                $token = $this->params->token;
                            }
                            Horde::checkRequestToken('object.' . $this->params->oaction, $token);

                            $action = $this->params->oaction;
                            $result = $this->object->$action();
                            if ($result === true) {
                                $this->koward->notification->push(sprintf(_("Successfully deleted the object \"%s\""),
                                                                          $this->params->id),
                                                                  'horde.message');
                            } else {
                                $this->koward->notification->push(_("Failed to delete the object."),
                                                                  'horde.error');
                            }
                            $this->urlFor(array('controller' => 'object',
                                                'action' => 'view',
                                                'id' => $this->params->id))
                                ->redirect();
                        }
                        if ($this->actions->validate()) {
                            $action = $this->actions->execute();
                            //FIXME: Hack
                            $result = $this->object->$action();

                            // Refresh the object view
			    $this->actions = null;
                            $this->object = $this->koward->getObject($this->params->id);
                            $buttons = $this->_getButtons($this->object, $this->object_type);
                            if (!empty($buttons)) {
                                $this->actions = new Koward_Form_Actions($this->object, $buttons);
                            }

                            $this->action_url = $this->urlFor(array('controller' => 'object',
                                                                    'action' => 'view',
                                                                    'id' => $this->params->id,
                                                                    'action' => $action,
                                                                    'token' => Horde::getRequestToken('object.' . $action)));
                            $this->return_url = $this->urlFor(array('controller' => 'object',
                                                                    'action' => 'view',
                                                                    'id' => $this->params->id));
                        }
                    } catch (Exception $e) {
                        $this->koward->notification->push($e->getMessage(), 'horde.error');
                    }
                }

                $this->vars = Horde_Variables::getDefaultVariables();
                $this->form = new Koward_Form_Object($this->vars, $this->object,
                                                    array('title' => _("View object")));
                $this->edit = Horde::link(
                    $this->urlFor(array('controller' => 'object',
                                        'action' => 'edit',
                                        'id' => $this->params->id)),
                    _("Edit")) . Horde::img('edit.png', _("Edit"))
                    . '</a>';


            }
        } catch (Exception $e) {
            $this->koward->notification->push($e->getMessage(), 'horde.error');
        }

        $this->render();
    }

    private function _getButtons(&$object, $type)
    {
        $actions = $object->getActions();
        if (!empty($actions)) {
            $buttons = array();
            foreach ($actions as $action) {
                if (isset($this->koward->objects[$type]['actions'][$action])
                    && $this->koward->hasAccess('object/action/' . $type . '/' . $action,
                                                Koward::PERM_EDIT)) {
                    $buttons[] = $this->koward->objects[$type]['actions'][$action];
                }
            }
        }
        return $buttons;
    }

    public function add()
    {
        $this->object = null;

        $type = Horde_Util::getFormData('type');

        if (!empty($type)) {
            $this->checkAccess($this->getPermissionId() . '/' . $type,
                               Koward::PERM_EDIT);
        } else {
            $this->checkAccess($this->getPermissionId(),
                               Koward::PERM_EDIT);
        }

        $this->_edit();
    }

    public function edit()
    {
        if (empty($this->params->id)) {
            $this->koward->notification->push(_("The object that should be viewed has not been specified."),
                                              'horde.error');
        } else {
            try {
                $this->object = $this->koward->getObject($this->params->id);
            } catch (Exception $e) {
                $this->koward->notification->push($e->getMessage(), 'horde.error');
            }

            $this->checkAccess($this->getPermissionId() . '/' . $this->koward->getType($this->object),
                               Koward::PERM_EDIT);

            $this->_edit();
        }
    }

    private function _edit()
    {
        try {
            $this->vars = Horde_Variables::getDefaultVariables();
            foreach ($this->params as $key => $value) {
                if (!$this->vars->exists($key)) {
                    if ($key != 'object' && is_array($value) && count($value) == 1) {
                        $this->vars->set($key, array_pop($value));
                    } else {
                        $this->vars->set($key, $value);
                    }
                }
            }
            $this->form = new Koward_Form_Object($this->vars, $this->object);

            if ($this->form->validate()) {
                $object = $this->form->execute();

                if (!empty($object)) {
                    $this->urlFor(array('controller' => 'object',
                                        'action' => 'view',
                                        'id' => $object->get(Horde_Kolab_Server_Object::ATTRIBUTE_UID)))
                        ->redirect();
                }
            }
        } catch (Exception $e) {
            $this->koward->notification->push($e->getMessage(), 'horde.error');
        }

        $this->post = $this->urlFor(array('controller' => $this->params['controller'],
                                          'action' => $this->params['action'],
                                          'id' => $this->params->id));

        $this->render();
    }

    public function search()
    {
        try {
            $this->vars = Horde_Variables::getDefaultVariables();
            $this->form = new Koward_Form_Search($this->vars, $this->object);

            $this->allowEdit = $this->koward->hasAccess('object/edit',
                                                        Koward::PERM_EDIT);
            $this->allowDelete = $this->koward->hasAccess('object/delete',
                                                          Koward::PERM_DELETE);

            if ($this->form->validate()) {
                if (isset($this->koward->search['list_attributes'])) {
                    $this->attributes = $this->koward->search['list_attributes'];
                } else {
                    $this->attributes = array(
                        'dn' => array(
                            'title' => _("Distinguished name"),
                            'width' => 100,
                            'link_view'=> true,
                        )
                    );
                }

                $this->objectlist = $this->form->execute(array_keys($this->attributes));

                $uids = array_keys($this->objectlist);

                if (count($uids) == 1) {
                    $this->urlFor(array('controller' => 'object',
                                        'action' => 'view',
                                        'id' => $uids[0]))
                        ->redirect();
                }
                if (count($uids) == 0) {
                    $this->koward->notification->push(_("No results found!"), 'horde.message');
                } else {
                    foreach ($this->objectlist as $uid => $info) {
                        $this->objectlist[$uid]['edit_url'] = Horde::link(
                            $this->urlFor(array('controller' => 'object',
                                                'action' => 'edit',
                                                'id' => $uid)),
                            _("Edit")) . Horde::img('edit.png', _("Edit"))
                            . '</a>';
                        $this->objectlist[$uid]['delete_url'] = Horde::link(
                            $this->urlFor(array('controller' => 'object',
                                                'action' => 'delete',
                                                'id' => $uid)),
                            _("Delete")) . Horde::img('delete.png', _("Delete"))
                            . '</a>';
                        $this->objectlist[$uid]['view_url'] = Horde::link(
                            $this->urlFor(array('controller' => 'object',
                                                'action' => 'view',
                                                'id' => $uid)), _("View"));
                        $this->objectlist[$uid]['__id'] = $uid;
                        if (!empty($this->koward->search['complex_attributes'])) {
                            $object = $this->koward->getObject($uid);
                            $this->objectlist[$uid] = array_merge($object->toHash(array_keys($this->attributes)), $this->objectlist[$uid]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->koward->notification->push($e->getMessage(), 'horde.error');
        }

        $this->post = $this->urlFor(array('controller' => 'object',
                                          'action' => 'search'));

        $this->render();
    }

}
