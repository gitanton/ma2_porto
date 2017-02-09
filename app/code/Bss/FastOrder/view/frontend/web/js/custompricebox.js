define(
    [
        'jquery',
        'Magento_Catalog/js/price-utils',
        'underscore',
        'mage/template',
        'jquery/ui',
        'priceBox',
    ],
    function ($, utils, _, mageTemplate) {

        'use strict';

        $.widget('bss.priceBox', $.mage.priceBox, {
            /**
             * Render price unit block.
             */
            _create: function createPriceBox()
            {
                var box = this.element;
                this.cache = {};
                this._setDefaultsFromPriceConfig();
                this._setDefaultsFromDataSet();

                box.on('reloadPrice', this.reloadPrice.bind(this));
                box.on('updatePrice', this.onUpdatePrice.bind(this));
            },


        });

        return $.bss.priceBox;
    }
);