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

namespace Itoris\MultipleWishlists\Block\Share\Email;

class Items extends \Magento\Wishlist\Block\Share\Email\Items
{
    protected $_template = 'Magento_Wishlist::email/items.phtml';
    
    public function getWishlistItems()
    {
        if (!$this->getDataHelper()->isEnabled()) return parent::getWishlistItems();
        
        if ($this->_collection === null) {
            $this->_collection = $this->_createWishlistItemCollection();
            
            $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $session = $this->_objectManager->get('Magento\Customer\Model\Session');
            $mWishlistId = (int) $session->getMWishlistIdForShare();

            $wishlists = $this->_objectManager->create('Itoris\MultipleWishlists\Model\Wishlist')
                                            ->getCollection()
                                            ->addCustomerFilter();
            $this->_currentWishlist = $wishlists->getWishlistById($mWishlistId);
            $itemIds = [0];
            foreach($this->_currentWishlist->getItems() as $item) $itemIds[] = $item->getWishlistItemId();
            
            $this->_collection->addFieldToFilter('wishlist_item_id', ['in' => $itemIds]);

            $this->_collection->clear()->load();

            $this->_prepareCollection($this->_collection);
        }
        return $this->_collection;
    }
    
    public function getDataHelper() {
        return \Magento\Framework\App\ObjectManager::getInstance()->get('Itoris\MultipleWishlists\Helper\Data');
    }
}