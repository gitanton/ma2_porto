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

class UpdateWishlist implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Backend\App\ConfigInterface $backendConfig
    ) {
        $this->_objectManager = $objectManager;
        $this->backendConfig = $backendConfig;
        $this->_request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        if (!$this->getDataHelper()->isEnabled()) return;
        
        $post = $this->_request->getPost();
        $mWishlistId = $post->get('mwishlist_id', 0);        
        $action = $post->get('mwishlist_action', '');
        $wishlistName = trim($post->get('mwishlist_name', ''));
        if (!$action) return;
        $response = [];

        $wishlists = $this->_objectManager->create('Itoris\MultipleWishlists\Model\Wishlist')
                                ->getCollection()
                                ->addCustomerFilter();
        $this->_currentWishlist = $wishlists->getWishlistById($mWishlistId);
        
        if ($action == 'update_wishlist') {
            $session = $this->_objectManager->get('Magento\Customer\Model\Session');
            $session->setCurrentMWishlist($mWishlistId);
            return;
        }
        
        if ($action == 'rename_wishlist') {
            if (!$this->_currentWishlist->getIsMainWishlist()) {
                if ($wishlistName) {
                    $this->_currentWishlist->setTitle($wishlistName)->save();
                    $response['wishlist_name'] = $wishlistName;
                    $response['success'] = 1;
                } else $response['error'] = __('The wishlist name can\'t be left blank');
            } else $response['error'] = __('You can\'t rename the main wishlist');
        }
        
        if ($action == 'remove_wishlist') {
            if (!$this->_currentWishlist->getIsMainWishlist()) {
                foreach($this->_currentWishlist->getItems() as $item) $item->getItem()->delete();
                $this->_currentWishlist->delete();
                $response['success'] = 1;
            } else $response['error'] = __('You can\'t remove the main wishlist');                
        }
        
        if ($action == 'create_wishlist') {
            if ($wishlistName) {
                $isNameUsed = $wishlistName == $wishlists->getMainWishlist()->getTitle();
                if (!$isNameUsed) {
                    foreach($wishlists as $mWishlist) {
                        if ($mWishlist->getTitle() == $wishlistName) {
                            $isNameUsed = true;
                            break;
                        }
                    }
                }
                if (!$isNameUsed) {
                    $mWishlist = $this->_objectManager->create('Itoris\MultipleWishlists\Model\Wishlist')
                                ->setWishlistId($wishlists->getMainWishlist()->getWishlistId())
                                ->setTitle($wishlistName)
                                ->setIsEditable(1)
                                ->setCreatedAt(date('Y-m-d H:i:s'))
                                ->setUpdatedAt(date('Y-m-d H:i:s'))
                                ->save();
                    $response['wishlist'] = [
                        'id' => $mWishlist->getMwishlistId(),
                        'name' => $mWishlist->getTitle(),
                        'qty' => 0,
                        'active' => 1
                    ];
                    $response['success'] = 1;
                } else $response['error'] = __('Wishlist with such a name already exists');
            } else $response['error'] = __('The wishlist name can\'t be left blank');
        }
        
        if ($action == 'copy_item' || $action == 'move_item') {
            if ($mWishlistId == -1) { //if create new wishlist check if name already exists
                if ($wishlistName) {
                    $isNameUsed = $wishlistName == $wishlists->getMainWishlist()->getTitle();
                    if (!$isNameUsed) {
                        foreach($wishlists as $mWishlist) {
                            if ($mWishlist->getTitle() == $wishlistName) {
                                $isNameUsed = true;
                                break;
                            }
                        }
                    }
                    if ($isNameUsed) $response['error'] = __('Wishlist with such a name already exists');
                } else $response['error'] = __('The wishlist name can\'t be left blank');
            }
            
            if (!isset($response['error'])) {
                $itemId = $post->get('item_id', 0);
                $mWishlistFrom = $post->get('mwishlist_from', 0);
                $this->_currentWishlist = $wishlists->getWishlistById($mWishlistFrom);
                $wishlistItem = $this->_currentWishlist->getItemById($itemId);
                if ($wishlistItem) {
                    $mWishlistItem = $wishlistItem->getMwishlistItem();
                    if ($action == 'copy_item') { //duplicate wishlist item
                        $wishlistItem = $this->_objectManager->create('Magento\Wishlist\Model\Item')
                                            ->setData($wishlistItem->getData())
                                            ->setWishlistItemId(null)
                                            ->save();
                                            
                        //copy buyRequest options
                        $collection = $this->_objectManager->get('Magento\Wishlist\Model\Item\OptionFactory')
                                    ->create()->getCollection()->addItemFilter([$itemId]);
                        $options = $collection->getOptionsByItem($itemId);
                        foreach($options as $option) $option->setOptionId(null)->setWishlistItemId($wishlistItem->getId())->save();
                        $wishlistItem->setOptions($options)->save();
                    }

                    if ($mWishlistId == -1) {//create mwishlist if needed
                        $mWishlistTo = $this->_objectManager->create('Itoris\MultipleWishlists\Model\Wishlist')
                                        ->setWishlistId($wishlists->getMainWishlist()->getWishlistId())
                                        ->setTitle($wishlistName)
                                        ->setIsEditable(1)
                                        ->setCreatedAt(date('Y-m-d H:i:s'))
                                        ->setUpdatedAt(date('Y-m-d H:i:s'))
                                        ->save();
                    } else $mWishlistTo = $wishlists->getWishlistById($mWishlistId);
                    
                    if (!$mWishlistTo->getIsMainWishlist()) {
                        if (!$mWishlistItem) { //create mwishlist item if needed
                            $mWishlistItem = $this->_objectManager->create('Itoris\MultipleWishlists\Model\WishlistItem')
                                            ->setAllowDelete(1);
                        }
                        if ($action == 'copy_item') { //duplicate mwishlist item
                            $mWishlistItem->setMwishlistItemId(null);
                        }
                        $mWishlistItem->setMwishlistId($mWishlistTo->getMwishlistId())                                
                                    ->setWishlistItemId($wishlistItem->getWishlistItemId())
                                    ->save();
                    } else if ($action == 'move_item') {//remove mwishlist item if moving to the main wishlist
                        $mWishlistItem->delete();
                    }
                    $response['success'] = 1;
                    $response['update_wishlists'] = [$this->_currentWishlist->getId(), $mWishlistTo->getId()];
                    if ($action == 'copy_item') $response['message'] = __('Product "%1" has been copied to wishlist "%2"', $wishlistItem->getProduct()->getName(), $mWishlistTo->getTitle());
                        else $response['message'] = __('Product "%1" has been moved to wishlist "%2"', $wishlistItem->getProduct()->getName(), $mWishlistTo->getTitle());
                } else $response['error'] = __('Item not found');
            }
        }
        
        if ($action == 'add_all_to_cart' || $action == 'add_to_cart') {
            $qtys = (array) json_decode($post->get('qtys', '{}'));
            $added = 0; $errors = []; $messages = [];
            $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');
            $isRemoveItemFromWishlist = !(int)$this->backendConfig->getValue('itoris_multiplewishlists/general/after_add_to_cart', 0);
            $response['removedItems'] = [];
            foreach($qtys as $itemId => $qty) {
                $wishlistItem = $this->_currentWishlist->getItemById((int) $itemId);
                if ($wishlistItem) {
                    if (intval($qty) <= 0) continue;

                    $options = $this->_objectManager->get('Magento\Wishlist\Model\Item\OptionFactory')
                                    ->create()->getCollection()->addItemFilter([$itemId]);
                    $wishlistItem->setOptions($options->getOptionsByItem($itemId));
                    $wishlistItem->setQty((int) $qty);
                    
                    $buyRequest = $this->_objectManager->get('Magento\Catalog\Helper\Product')
                        ->addParamsToBuyRequest(
                            $this->_request->getParams(),
                            ['current_config' => $wishlistItem->getBuyRequest()]
                        );
                    $wishlistItem->mergeBuyRequest($buyRequest);
                    $isAdded = 0;
                    try {                        
                        $isAdded = (int) $wishlistItem->addToCart($cart, $isRemoveItemFromWishlist);
                        if ($isAdded && $isRemoveItemFromWishlist) $response['removedItems'][] = $wishlistItem->getId();
                    } catch (\Magento\Catalog\Model\Product\Exception $e) {
                        $errors[] = __('Product "%1" is out of stock.', $wishlistItem->getProduct()->getName());
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        $errors[] = __('We can\'t add "%1" to the cart right now.', $wishlistItem->getProduct()->getName()).' '.$e->getMessage();
                    } catch (\Exception $e) {
                        $errors[] = __('We can\'t add "%1" to the cart right now.', $wishlistItem->getProduct()->getName());
                    }
                    
                    $added += (int) $isAdded;
                    
                } else $errors[] = __('Wishlist item with ID %1 was not found!', $itemId);
            }
            $cart->save()->getQuote()->collectTotals();

            if ($added == 0) {
                $errors[] = __('No items were added to cart');
            } else if ($added == count($qtys)) {
                $response['success'] = 1;
                if ($action == 'add_to_cart' && isset($wishlistItem)) {
                    $response['message'] = __('"%1" has been added to your shopping cart', $wishlistItem->getProduct()->getName());
                } else {
                    $response['message'] = __('All items were added to cart');
                }
            } else if ($added > 0) {
                $response['success'] = 1;
                $response['message'] = __('%1 of %2 items were added to cart', $added, count($qtys));
            }
            
            if (!empty($errors)) $response['errors'] = $errors;

        }
        
        $block = $this->_objectManager->create('Itoris\MultipleWishlists\Block\Popup');
        $response['wishlists'] = $block->getWishlists();
        $response['popup_html'] = $block->setTemplate('Itoris_MultipleWishlists::popup.phtml')->toHtml();
        
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