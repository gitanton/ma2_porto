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

namespace Itoris\MultipleWishlists\Block\Customer\Wishlist;

class Items extends \Magento\Wishlist\Block\Customer\Wishlist\Items
{
    protected $_currentWishlist = false;
    
    public function setTemplate($template) {
        if ($this->getDataHelper()->isEnabled()) {
            return parent::setTemplate('Itoris_MultipleWishlists::item/list.phtml');
        } else {
            return parent::setTemplate('Magento_Wishlist::item/list.phtml');
        }
    }
    
    public function getItems(){
        $this->_originalItems = parent::getItems();
        if (!$this->getDataHelper()->isEnabled()) return $this->_originalItems;
        
        $wishlist = $this->getCurrentWishslist();
        if (!$wishlist->getIsMainWishlist()) {
            $items = [];
            foreach($wishlist->getItems() as $item) $items[] = $this->_originalItems->getItemById($item->getWishlistItemId());
            return $items;
        }
        return $wishlist->getItems();
    }
    
    public function toHtml(){
        if (!$this->getDataHelper()->isEnabled()) return parent::toHtml();
        
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $request = $this->_objectManager->get('Magento\Framework\App\RequestInterface');
        if ($request->getParam('isAjax', '')) {
            $response = $this->_objectManager->get('Magento\Framework\App\Console\Response');
            $response->terminateOnSend(true);
            $response->setBody(parent::toHtml());
            $response->sendResponse();
        }
        return parent::toHtml();
    }
    
    public function getProductImage($product){
        return $this->_objectManager->get('Magento\Catalog\Helper\Image')->init($product, 'wishlist_thumbnail');
    }
    
    public function getCurrentWishslist() {
        if ($this->_currentWishlist) return $this->_currentWishlist;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $request = $this->_objectManager->get('Magento\Framework\App\RequestInterface');
        $post = $request->getPost();
        $wishlists = $this->_objectManager->create('Itoris\MultipleWishlists\Model\Wishlist')
                                ->getCollection()
                                ->addCustomerFilter();
                                
        $session = $this->_objectManager->get('Magento\Customer\Model\Session');
        $currentMWishlistId = (int) $session->getCurrentMWishlist();
        $session->unsCurrentMWishlist();
        
        if ($currentMWishlistId) {
            $this->_currentWishlist = $wishlists->getWishlistById($currentMWishlistId);
        } else if ($post->get('mwishlist_id', 0)) {
            $this->_currentWishlist = $wishlists->getWishlistById($post->get('mwishlist_id', 0));
        } else {
            $this->_currentWishlist = $wishlists->getWishlistByName($request->getParam('mwishlist', ''));
        }
        return $this->_currentWishlist;
    }
    
    public function getDataHelper() {
        return \Magento\Framework\App\ObjectManager::getInstance()->get('Itoris\MultipleWishlists\Helper\Data');
    }
}