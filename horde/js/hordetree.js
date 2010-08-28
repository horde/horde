/**
 * Provides the javascript class to create dynamic trees.
 *
 * Optionally uses the Horde_Tooltip class (tooltips.js).
 *
 * Custom Events
 * -------------
 * The 'memo' property of the Event object contains the original event object.
 *
 * 'Horde_Tree:expand'
 *   Fired when a tree element is expanded.
 *
 * 'Horde_Tree:collapse'
 *   Fired when a tree element is collapsed.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 */

var Horde_Tree = Class.create({

    initialize: function(opts)
    {
        this.opts = opts;

        if (this.opts.initTree) {
            this.renderTree(this.opts.initTree.nodes, this.opts.initTree.root_nodes, this.opts.initTree.is_static);
            this.opts.initTree = null;
        }

        $(this.opts.target).observe('click', this._onClick.bindAsEventListener(this));

        this.opts.ie6 = (navigator.userAgent.toLowerCase().substr(25,6)=="msie 6");
    },

    renderTree: function(nodes, rootNodes, renderStatic)
    {
        this.nodes = nodes;
        this.rootNodes = rootNodes;
        this.renderStatic = renderStatic;
        this.dropline = [];
        this.output = document.createDocumentFragment();

        this._buildHeader();

        this.rootNodes.each(function(r) {
            this.buildTree(r, this.output);
        }, this);

        $(this.opts.target).update('');
        $(this.opts.target).appendChild(this.output);

        if (this.opts.ie6) {
            /* Correct for frame scrollbar in IE6 by determining if a scrollbar
             * is present, and if not readjusting the marginRight property to
             * 0. See http://www.xs4all.nl/~ppk/js/doctypes.html for why this
             * works */
            if (document.documentElement.clientHeight == document.documentElement.offsetHeight) {
                // no scrollbar present, take away extra margin
                $(document.body).setStyle({ marginRight: 0 });
            } else {
                $(document.body).setStyle({ marginRight: '15px' });
            }
        }

        // If using alternating row shading, work out correct shade.
        if (this.opts.options.alternate) {
            this.stripe();
        }

        if (window.Horde_Tooltips) {
            window.Horde_ToolTips.attachBehavior();
        }
    },

    _buildHeader: function()
    {
        if (this.opts.options.hideHeaders ||
            !this.opts.header.size()) {
            return;
        }

        var div = new Element('DIV');

        this.opts.header.each(function(h) {
            var tmp = new Element('SPAN').insert(h.html ? h.html : '&nbsp;');

            if (h['class']) {
                tmp.addClassName(h['class']);
            }

            div.appendChild(tmp);
        }, this);

        this.output.appendChild(div);
    },

    // Recursive function to walk through the tree array and build
    // the output.
    buildTree: function(nodeId, p)
    {
        var last_subnode, tmp,
            node = this.nodes[nodeId];

        this.buildLine(nodeId, p);

        if (!Object.isUndefined(node.children)) {
            last_subnode = node.children.last();
            tmp = new Element('DIV', { id: 'nodeChildren_' + nodeId });
            [ tmp ].invoke(node.expanded ? 'show' : 'hide');

            node.children.each(function(c) {
                if (c == last_subnode) {
                    this.nodes[c].node_last = true;
                }
                this.buildTree(c, tmp);
            }, this);

            p.appendChild(tmp);
        }
    },

    buildLine: function(nodeId, p)
    {
        var div, label, tmp,
            column = 0,
            node = this.nodes[nodeId];

        div = new Element('DIV', { className: 'treeRow' });
        if (node['class']) {
            div.addClassName(node['class']);
        }

        // If we have headers, track which logical "column" we're in for
        // any given cell of content.
        if (node.extra && node.extra[0]) {
            node.extra[0].each(function(n) {
                div.insert(this._divClass(new Element('SPAN').update(n), column++));
            }, this);
        }

        for (; column < this.opts.extraColsLeft; ++column) {
            div.insert(this._divClass(new Element('SPAN').update('&nbsp;'), column));
        }

        div.insert(this._divClass(new Element('SPAN'), column));

        tmp = document.createDocumentFragment();
        for (i = Number(this.renderStatic); i < node.indent; ++i) {
            tmp.appendChild(new Element('SPAN').addClassName('treeImg').addClassName(
                'treeImg' + ((this.dropline[i] && this.opts.options.lines)
                    ? this.opts.imgLine
                    : this.opts.imgBlank)
            ));
        }

        tmp.appendChild(this._setNodeToggle(nodeId));

        if (node.url) {
            label = new Element('A', { href: node.url }).insert(
                this._setNodeIcon(nodeId)
            ).insert(
                node.label
            );

            if (node.urlclass) {
                label.addClassName(node.urlclass);
            } else if (this.opts.options.urlclass) {
                label.addClassName(this.opts.options.urlclass);
            }

            if (node.title) {
                label.writeAttribute('title', node.title);
            }

            if (node.target) {
                label.writeAttribute('target', node.target);
            } else if (this.opts.options.target) {
                label.writeAttribute('target', this.opts.options.target);
            }

            //if (node.onclick) {
            //    label.push(' onclick="' + node.onclick + '"');
            //}

            label = label.wrap('SPAN');
        } else {
            label = new Element('SPAN').addClassName('toggle').insert(
                this._setNodeIcon(nodeId)
            ).insert(
                node.label
            );
        }

        if (this.opts.options.multiline) {
            div.insert(new Element('TABLE').insert(
                new Element('TR').insert(
                    new Element('TD').appendChild(tmp)
                ).insert(
                    new Element('TD').insert(label)
                )
            ));
        } else {
            div.appendChild(tmp);
            div.insert(label)
        }

        ++column;

        if (node.extra && node.extra[1]) {
            node.extra[1].each(function(n) {
                div.insert(this._divClass(new Element('SPAN').update(n), column++));
            }, this);
        }

        for (; column < this.opts.extraColsRight; ++column) {
            div.insert(this._divClass(new Element('SPAN').update('&nbsp;'), column));
        }

        p.appendChild(div);
    },

    _divClass: function(div, c)
    {
        if (this.opts.header[c] && this.opts.header[c]['class']) {
            div.addClassName(this.opts.header[c]['class']);
        }

        return div;
    },

    _setNodeToggle: function(nodeId)
    {
        var node = this.nodes[nodeId];

        if (node.indent == '0') {
            // Top level with children.
            this.dropline[0] = false;

            if (this.renderStatic) {
                return '';
            }

            switch (this._getLineType(nodeId)) {
            case 1:
            case 2:
                this.dropline[0] = true;
                break;
            }
        } else {
            this.dropline[node.indent] = !node.node_last;
        }

        return new Element('SPAN', { id: "nodeToggle_" + nodeId }).addClassName('treeToggle').addClassName('treeImg').addClassName('treeImg' + this._getNodeToggle(nodeId));
    },

    _getNodeToggle: function(nodeId)
    {
        var type,
            node = this.nodes[nodeId];

        if (node.indent == '0') {
            if (this.renderStatic) {
                return '';
            }

            if (!this.opts.options.lines) {
                return this.opts.imgBlank;
            }

            type = this._getLineType(nodeId);

            if (node.children) {
                // Top level with children.
                if (node.expanded) {
                    return type
                        ? ((type == 2) ? this.opts.imgMinus : this.opts.imgMinusBottom)
                        : this.opts.imgMinusOnly;
                }

                return type
                    ? ((type == 2) ? this.opts.imgPlus : this.opts.imgPlusBottom)
                    : this.opts.imgPlusOnly;
            }

            switch (type) {
            case 0:
                return this.opts.imgNullOnly;

            case 1:
                return this.opts.imgJoinTop;

            case 2:
                return this.opts.imgJoin;

            case 3:
                return this.opts.imgJoinBottom;
            }
        }

        if (node.children) {
            // Node with children.
            if (!node.node_last) {
                // Not last node.
                if (!this.opts.options.lines) {
                    return this.opts.imgBlank;
                } else if (this.renderStatic) {
                    return this.opts.imgJoin;
                } else if (node.expanded) {
                    return this.opts.imgMinus;
                }

                return this.opts.imgPlus;
            }

            // Last node.
            if (!this.opts.options.lines) {
                return this.opts.imgBlank;
            } else if (this.renderStatic) {
                return this.opts.imgJoinBottom;
            } else if (node.expanded) {
                return this.opts.imgMinusBottom;
            }

            return this.opts.imgPlusBottom;
        }

        // Node no children.
        if (!node.node_last) {
            // Not last node.
            return this.opts.options.lines
                ? this.opts.imgJoin
                : this.opts.imgBlank;
        }

        // Last node.
        return this.opts.options.lines
            ? this.opts.imgJoinBottom
            : this.opts.imgBlank;
    },

    _getLineType: function(nodeId)
    {
        if (this.opts.options.lines_base &&
            this.rootNodes.size() > 1) {
            switch (this.rootNodes.indexOf(nodeId)) {
            case 0:
                return 1;

            case (this.rootNodes.size() - 1):
                return 3;

            default:
                return 2;
            }
        }

        return 0;
    },

    _setNodeIcon: function(nodeId)
    {
        var img,
            node = this.nodes[nodeId];

        // Image.
        if (node.icon) {
            // Node has a user defined icon.
            img = new Element('IMG', { id: "nodeIcon_" + nodeId, src: (node.iconopen && node.expanded ? node.iconopen : node.icon) }).addClassName('treeIcon')
        } else {
            img = new Element('SPAN', { id: "nodeIcon_" + nodeId }).addClassName('treeIcon');
            if (node.children) {
                // Standard icon: node with children.
                img.addClassName('treeImg' + (node.expanded ? this.opts.imgFolderOpen : this.opts.imgFolder));
            } else {
                // Standard icon: node, no children.
                img.addClassName('treeImg' + this.opts.imgLeaf);
            }
        }

        if (node.iconalt) {
            img.writeAttribute('alt', node.iconalt);
        }

        return img;
    },

    toggle: function(nodeId)
    {
        var icon, nodeToggle, toggle, children,
            node = this.nodes[nodeId];

        node.expanded = !node.expanded;
        if (children = $('nodeChildren_' + nodeId)) {
            children.setStyle({ display: node.expanded ? 'block' : 'none' });
        }

        // Toggle the node's icon if it has separate open and closed
        // icons.
        if (icon = $('nodeIcon_' + nodeId)) {
            // Image.
            if (node.icon) {
                icon.writeAttribute('src', (node.expanded && node.iconopen) ? node.iconopen : node.icon);
            } else {
                // Use standard icon set.
                icon.writeAttribute('src', node.expanded ? this.opts.imgFolderOpen : this.opts.imgFolder);
            }
        }

        // If using alternating row shading, work out correct shade.
        if (this.opts.options.alternate) {
            this.stripe();
        }

        if (toggle = $('nodeToggle_' + nodeId)) {
            toggle.writeAttribute('class', 'treeToggle treeImg').addClassName('treeImg' + this._getNodeToggle(nodeId));
        }

        $(this.opts.target).fire(node.expanded ? 'Horde_Tree:expand' : 'Horde_Tree:collapse', nodeId);

        this.saveState(nodeId, node.expanded)
    },

    stripe: function()
    {
        var classes = [ 'rowEven', 'rowOdd' ],
            i = 0;

        $(this.opts.target).select('DIV.treeRow').each(function(r) {
            classes.each(r.removeClassName.bind(r));
            if (r.clientHeight) {
                r.addClassName(classes[++i % 2]);
            }
        });
    },

    saveState: function(nodeId, expanded)
    {
        if (this.opts.nocookie) {
            return;
        }

        var newCookie = '',
            newNodes = [],
            oldCookie = this._getCookie(this.opts.target + '_expanded');

        if (expanded) {
            // Expand requested so add to cookie.
            newCookie = (oldCookie ? oldCookie + ',' : '') + nodeId;
        } else {
            // Collapse requested so remove from cookie.
            oldCookie.split(',').each(function(n) {
                if (n != nodeId) {
                    newNodes[newNodes.length] = n
                }
            });
            newCookie = newNodes.join(',');
        }

        document.cookie = this.opts.target + '_expanded=exp' + escape(newCookie) + ';DOMAIN=' + this.opts.cookieDomain + ';PATH=' + this.opts.cookiePath + ';';
    },

    _getCookie: function(name)
    {
        var end,
            dc = document.cookie,
            prefix = name + '=exp',
            begin = dc.indexOf('; ' + prefix);

        if (begin == -1) {
            begin = dc.indexOf(prefix);
            if (begin != 0) {
                return '';
            }
        } else {
            begin += 2;
        }

        end = document.cookie.indexOf(';', begin);
        if (end == -1) {
            end = dc.length;
        }

        return unescape(dc.substring(begin + prefix.length, end));
    },

    _onClick: function(e)
    {
        var elt = e.element(),
            id = elt.readAttribute('id');

        if (elt.hasClassName('treeIcon')) {
            elt = elt.up().previous();
        } else if (elt.hasClassName('toggle')) {
            elt = elt.previous();
        }

        id = elt.readAttribute('id');
        if (id && id.startsWith('nodeToggle_')) {
            this.toggle(id.substr(11));
            e.stop();
        }
    }

});
