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

namespace Itoris\MultipleWishlists\Model\ResourceModel\Wishlist;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected $mainWishlist;

    protected function _construct() {
        $this->_init('Itoris\MultipleWishlists\Model\Wishlist', 'Itoris\MultipleWishlists\Model\ResourceModel\Wishlist');
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->mainWishlist = $this->_objectManager->create('Itoris\MultipleWishlists\Model\Wishlist')
                                ->setIsMainWishlist(true)
                                ->setMwishlistId(0)
                                ->setTitle(__('Main'));
    }
    
    public function addCustomerFilter($customerId = 0) {
        if (!$customerId) $customerId = $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomerId();
        $wishlist = $this->_objectManager->create('Magento\Wishlist\Model\Wishlist')->loadByCustomerId((int) $customerId);
        $this->addFieldToFilter('wishlist_id', ['eq' => (int) $wishlist->getId()]);
        $this->getMainWishlist()->setWishlistId($wishlist->getId())->setCollection($this);
        return $this;
    }
    
    public function getMainWishlist(){
        return $this->mainWishlist;
    }
    
    public function getWishlistByName($name){
        foreach($this as $wishlist) {
            if ($wishlist->getTitle() == $name) return $wishlist;
        }
        return $this->getMainWishlist();
    }
    
    public function getWishlistById($id){
        foreach($this as $wishlist) {
            if ($wishlist->getId() == $id) return $wishlist;
        }
        return $this->getMainWishlist();
    }    
}
