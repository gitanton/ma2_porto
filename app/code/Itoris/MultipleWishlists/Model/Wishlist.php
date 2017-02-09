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

namespace Itoris\MultipleWishlists\Model;

class Wishlist extends \Magento\Framework\Model\AbstractModel {

    protected $isMainWishlist;
    protected $wishlist;
    protected $customer;
    protected $items;
    
    protected function _construct() {
        $this->_init('Itoris\MultipleWishlists\Model\ResourceModel\Wishlist');
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->setIsMainWishlist(false);
    }
    
    public function setIsMainWishlist($isMain = false) {
        $this->isMainWishlist = $isMain;
        return $this;
    }
    
    public function getIsMainWishlist() {
        return $this->isMainWishlist;
    }
    
    public function getWishlist() {
        if (!$this->wishlist) $this->wishlist = $this->_objectManager->create('Magento\Wishlist\Model\Wishlist')->load($this->getWishlistId());
        return $this->wishlist;
    }
    
    public function getCustomer() {
        if (!$this->customer) $this->customer = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($this->getWishlist()->getCustomerId());
        return $this->customer;
    }
    
    public function getItems(){
        if (!$this->items) {
            if ($this->getIsMainWishlist()) {
                $itemIds = [];
                foreach($this->getCollection() as $mWishlist) {
                    foreach($mWishlist->getItems() as $item) $itemIds[] = (int) $item->getWishlistItemId();
                }
                $this->items = $this->_objectManager->create('Magento\Wishlist\Model\Item')
                                ->getCollection()
                                ->addFieldToFilter('wishlist_id', ['eq' => $this->getWishlistId()]);
                if (!empty($itemIds)) $this->items->addFieldToFilter('wishlist_item_id', ['nin' => $itemIds]);                
            } else {
                $this->items = $this->_objectManager->create('Itoris\MultipleWishlists\Model\WishlistItem')
                                ->getCollection()
                                ->addMWishlistFilter($this->getMwishlistId());
            }
        }
        return $this->items;
    }
    
    public function getItemById($itemId){
        $items = $this->getItems();
        foreach($items as $item) {
            if (!$this->getIsMainWishlist()) $item = $item->getItem()->setMwishlistItem($item);
            if ($itemId == $item->getId()) return $item;
        }
    }
}
