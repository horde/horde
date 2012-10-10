/**
 * Scripts for the Horde topbar.
 */
var HordeTopbar = {
    // Vars used and defaulting to null/false:
    //   conf, searchGhost

    /**
     * Updates the date in the sub bar.
     */
    updateDate: function()
    {
        var d = $('horde-sub-date');

        if (d) {
            d.update(Date.today().toString(this.conf.format));
            this.updateDate.bind(this).delay(10);
        }
    },

    refreshTopbar: function()
    {
        var params = $H({ app: this.conf.app });
        if (this.conf.SID) {
            params.update(this.conf.SID.toQueryParams());
        }
        params.set('token', this.conf.TOKEN);
        new Ajax.Request(this.conf.URI_AJAX + 'topbarUpdate', {
            onComplete: this.onUpdateTopbar.bind(this),
            parameters: params
        });
    },

    onUpdateTopbar: function(response)
    {
        if (!response.responseJSON) {
            return;
        }

        var r = response.responseJSON.response;
        $('horde-navigation').update();

        this._renderTree(r.nodes, r.root_nodes);
        r.files.each(function(file) {
            $$('head')[0].insert(new Element('script', { src: file }));
        });
    },

    _renderTree: function(nodes, root_nodes)
    {
        root_nodes.each(function(root_node) {
            var elm, item,
                active = nodes[root_node].active ? '-active' : '',
                container = new Element('DIV', { className: nodes[root_node].class });
            if (nodes[root_node].url) {
                elm = new Element('A', { className: 'horde-mainnavi' + active, href: nodes[root_node].url });
                if (nodes[root_node].onclick) {
                    elm.writeAttribute('onclick', nodes[root_node].onclick);
                }
                container.insert(elm);
            } else {
                elm = container;
            }
            item = new Element('LI').insert(container);
            if (nodes[root_node].children) {
                if (!nodes[root_node].noarrow) {
                    elm.insert(new Element('SPAN', { className: 'horde-point-arrow' + active })
                               .insert('&#9662;'));
                }
                item.insert(this._renderBranch(nodes, nodes[root_node].children))
            }
            elm.insert(nodes[root_node].label);
            $('horde-navigation')
                .insert(new Element('DIV', { className: 'horde-navipoint' })
                        .insert(new Element('DIV', { className: 'horde-point-left' + active }))
                        .insert(new Element('UL', { className: 'horde-dropdown' })
                                .insert(item))
                        .insert(new Element('DIV', { className: 'horde-point-right' + active })));
        }, this);
    },

    _renderBranch: function(nodes, children)
    {
        var list = new Element('UL');
        children.each(function(child) {
            var container, elm, item,
                attr = nodes[child].children
                    ? { className: 'arrow' }
                    : undefined;
            container = new Element('DIV', { className: 'horde-drowdown-str' });
            if (nodes[child].url) {
                elm = new Element('A', { className: 'horde-mainnavi', href: nodes[child].url });
                if (nodes[child].onclick) {
                    elm.writeAttribute('onclick', nodes[child].onclick);
                }
                container.insert(elm);
            } else {
                elm = container;
            }
            elm.insert(nodes[child].label);
            item = new Element('LI', attr).insert(container);
            if (nodes[child].children) {
                item.insert(this._renderBranch(nodes, nodes[child].children))
            }
            list.insert(item);
        }, this);
        return list;
    },

    onDomLoad: function()
    {
        if ($('horde-search-input')) {
            this.searchGhost = new FormGhost('horde-search-input');
        }
        this.updateDate();
        new PeriodicalExecuter(this.refreshTopbar.bind(this),
                               this.conf.refresh);
    }
}

document.observe('dom:loaded', HordeTopbar.onDomLoad.bind(HordeTopbar));
