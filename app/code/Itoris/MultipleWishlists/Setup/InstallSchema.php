<?php
/**
 * ITORIS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the ITORIS's Magento Extensions License Agreement
 * which is available through the world-wide-web at this URL:
 * http://www.itoris.com/magento-extensions-license.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to sales@itoris.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extensions to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to the license agreement or contact sales@itoris.com for more information.
 *
 * @category   ITORIS
 * @package    ITORIS_M2_MULTIPLE_WISHLISTS
 * @copyright  Copyright (c) 2016 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

namespace Itoris\MultipleWishlists\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        
        $setup->run("
            CREATE TABLE IF NOT EXISTS {$setup->getTable('itoris_mwishlist')} (
              `mwishlist_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `wishlist_id` int(10) unsigned NOT NULL,
              `title` varchar(255) NOT NULL,
              `is_editable` tinyint(3) unsigned NOT NULL,
              `created_at` datetime NOT NULL,
              `updated_at` datetime NOT NULL,
              PRIMARY KEY (`mwishlist_id`),
              KEY `wishlist_id` (`wishlist_id`),
              KEY `title` (`title`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
        ");
        
        $setup->run("
            CREATE TABLE IF NOT EXISTS {$setup->getTable('itoris_mwishlist_item')} (
              `mwishlist_item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `wishlist_item_id` int(10) unsigned NOT NULL,
              `mwishlist_id` int(10) unsigned NOT NULL,
              `allow_delete` tinyint(3) unsigned NOT NULL,
              PRIMARY KEY (`mwishlist_item_id`),
              KEY `wishlist_item_id` (`wishlist_item_id`),
              KEY `mwishlist_id` (`mwishlist_id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
        ");
        
        $setup->run("
            ALTER TABLE {$setup->getTable('itoris_mwishlist')}
              ADD CONSTRAINT `itoris_mwishlist_ibfk_1` FOREIGN KEY (`wishlist_id`) REFERENCES {$setup->getTable('wishlist')} (`wishlist_id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ");
        
        $setup->run("
            ALTER TABLE {$setup->getTable('itoris_mwishlist_item')}
              ADD CONSTRAINT `itoris_mwishlist_item_ibfk_1` FOREIGN KEY (`wishlist_item_id`) REFERENCES {$setup->getTable('wishlist_item')} (`wishlist_item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
              ADD CONSTRAINT `itoris_mwishlist_item_ibfk_2` FOREIGN KEY (`mwishlist_id`) REFERENCES {$setup->getTable('itoris_mwishlist')} (`mwishlist_id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ");
        
        $setup->run("
            INSERT INTO {$setup->getTable('core_config_data')} (`scope`, `scope_id`, `path`, `value`) VALUES ('default', 0, 'itoris_multiplewishlists/general/enabled', '1');
        ");
        $setup->run("
            INSERT INTO {$setup->getTable('core_config_data')} (`scope`, `scope_id`, `path`, `value`) VALUES ('default', 0, 'itoris_multiplewishlists/general/after_wishlist_selected', '1');
        ");
        $setup->run("
            INSERT INTO {$setup->getTable('core_config_data')} (`scope`, `scope_id`, `path`, `value`) VALUES ('default', 0, 'itoris_multiplewishlists/general/after_add_to_cart', '1');
        ");
        
        $setup->endSetup();
    }
}