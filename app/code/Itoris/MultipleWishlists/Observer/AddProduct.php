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

namespace Itoris\MultipleWishlists\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;

class AddProduct implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_objectManager = $objectManager;
        $this->_request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        if (!$this->getDataHelper()->isEnabled()) return;
        
        $event = $observer->getEvent();
        $wishlist = $event->getWishlist();
        $product = $event->getProduct();
        $item = $event->getItem();
        $session = $this->_objectManager->get('Magento\Customer\Model\Session');
        $this->_request->setParams(array_merge((array) $session->getBeforeMwishlistRequest(), $this->_request->getParams()));
        $session->unsBeforeMwishlistRequest();
        $mWishlistId = (int) $this->_request->getParam('mwishlist_id', 0);
        $mWishlistTitle = trim($this->_request->getParam('mwishlist_name', ''));
        $mWishlistAjax = (int) $this->_request->getParam('mwishlist_ajax', 0);
        $response = [];
        
        $wishlists = $this->_objectManager->create('Itoris\MultipleWishlists\Model\Wishlist')
                                        ->getCollection()
                                        ->addCustomerFilter();
        $mWishlist = $wishlists->getWishlistById($mWishlistId);
        if ($mWishlistId >= 0) $mWishlistId = $mWishlist->getMwishlistId();
        
        if ($mWishlistId > 0) {
            //associate wishlist item with existing wishlist
            $mWishlist = $this->_objectManager->create('Itoris\MultipleWishlists\Model\Wishlist')->load($mWishlistId);
            $mWishlist->setUpdatedAt(date('Y-m-d H:i:s'))->save();
        } else if ($mWishlistId == 0) {
            //main wishlist, do nothing here
        } else {
            //check if wishlist already exists
            $mWishlistFound = false;
            foreach($wishlists as $wishlist) {
                if ($wishlist->getTitle() == $mWishlistTitle) {
                    $mWishlistFound = $wishlist;
                    break;
                }
            }
            if (!$mWishlistFound) {
                //create a new wishlist
                $mWishlist = $this->_objectManager->create('Itoris\MultipleWishlists\Model\Wishlist');
                $mWishlist->setWishlistId($wishlist->getWishlistId());
                $mWishlist->setTitle($mWishlistTitle);
                $mWishlist->setIsEditable(1);
                $mWishlist->setCreatedAt(date('Y-m-d H:i:s'));
                $mWishlist->setUpdatedAt(date('Y-m-d H:i:s'));
                $mWishlist->save();
            } else {
                $mWishlist = $mWishlistFound;
            }
        }
        
        if ($mWishlistId != 0) {
            $mWishlistItem = $this->_objectManager->create('Itoris\MultipleWishlists\Model\WishlistItem');
            $mWishlistItem->setWishlistItemId($item->getId());
            $mWishlistItem->setMwishlistId($mWishlist->getMwishlistId());
            $mWishlistItem->setAllowDelete(1);
            $mWishlistItem->save();
        }
        if (!$mWishlistAjax) return;
        
        $response['message'] = sprintf(__('Product "%s" has been added to wishlist "%s"'), $product->getName(), $mWishlistId ? $mWishlist->getTitle() : __('Main'));
        $response['popup_html'] = $this->_objectManager->create('Itoris\MultipleWishlists\Block\Popup')->setTemplate('Itoris_MultipleWishlists::popup.phtml')->toHtml();
        $response['success'] = 1;
        header('Content-Type: application/json');
        $responseObj = $this->_objectManager->get('Magento\Framework\App\Console\Response');
        $responseObj->terminateOnSend(true);
        $responseObj->setBody(json_encode($response));
        $responseObj->sendResponse();
    }
    
    public function getDataHelper() {
        return $this->_objectManager->get('Itoris\MultipleWishlists\Helper\Data');
    }
}