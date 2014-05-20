/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    return {

        view: true,

        fullSize: {
            width: true
        },

        header: function() {
            return {
                title: 'pim.products.title',
                noBack: true,

                breadcrumb: [
                    {title: 'navigation.pim'},
                    {title: 'pim.products.title'}
                ]
            };
        },

        templates: ['/admin/productbase/template/product/list'],

        initialize: function() {
            this.render();
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/productbase/template/product/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'productsFields', '/admin/productbase/api/productbases/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'productsToolbar',
                    inHeader: true,
                    template: [{
                            'id': 1,
                            'icon': 'plus-circle',
                            'title': 'Add User',
                            'class': 'highlight-white',
                            disabled: true
                        },
                        {
                            'id': 2,
                            'icon': 'trash-o',
                            'title': 'Delete User',
                            'disabled': true
                        },
                        {
                            'id': 'import',
                            'icon': 'cloud-upload',
                            'title': 'Import',
                            'group': '2',
                            callback: function() {
                                this.sandbox.emit('sulu.pim.products.import');
                            }.bind(this)
                        },
                        {
                            'icon': 'cloud-download',
                            'title': 'Export',
                            disabled: true
                        }
                    ]
                },
                {
                    el: this.sandbox.dom.find('#products-list', this.$el),
                    url: '/admin/productbase/api/productbases?flat=true',
                    viewOptions: {
                        table: {
                            fullWidth: true
                        }
                    }
                }
            );
        }
    };
});
