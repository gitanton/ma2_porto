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
 
namespace Itoris\MultipleWishlists\Controller\Index;

use Magento\Checkout\Helper\Cart as CartHelper;
use Magento\Checkout\Model\Cart as CheckoutCart;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\Helper\Data as WishlistHelper;


class Fromcart extends \Magento\Wishlist\Controller\Index\Fromcart
{

    public function execute()
    {
        if (!$this->getDataHelper()->isEnabled()) return parent::execute();
        
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (isset($this->formKeyValidator) && !$this->formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('*/*/');
        }

        $wishlist = $this->wishlistProvider->getWishlist();
        if (!$wishlist) {
            throw new NotFoundException(__('Page not found.'));
        }

        try {
            $itemId = (int)$this->getRequest()->getParam('item');
            $item = $this->cart->getQuote()->getItemById($itemId);
            if (!$item) {
                throw new LocalizedException(
                    __('The requested cart item doesn\'t exist.')
                );
            }

            $productId = $item->getProductId();
            $buyRequest = $item->getBuyRequest();
            $wishlistItem = $wishlist->addNewItem($productId, $buyRequest);

            $this->cart->getQuote()->removeItem($itemId);
            $this->cart->save();

            $this->wishlistHelper->calculate();
            $wishlist->save();

            $prevQty = (int)$wishlistItem->getQty() - (int)$item->getQty();
            
            $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            
            if ($prevQty > 0) {
                //capturing buyRequest options
                $collection = $this->_objectManager->get('Magento\Wishlist\Model\Item\OptionFactory')
                            ->create()->getCollection()->addItemFilter([$wishlistItem->getId()]);
                $options = $collection->getOptionsByItem($wishlistItem->getId());
                
                //clonning wishlist item
                $wishlistItem->setQty($prevQty)->save(); //restoring quantity
                foreach($options as $option) $option->setOptionId(null)->setWishlistItemId($wishlistItem->getId())->save(); //cloning options
                
                $wishlistItem->setWishlistItemId(null)->setQty((int)$item->getQty())->setOptions($options)->save(); //creating item duplicate
                
            }
            
            //moving cloned/or native item into selected wishlist
            $mWishlistObserver = $this->_objectManager->get('Itoris\MultipleWishlists\Observer\AddProduct');
            $event = new \Magento\Framework\DataObject();
            $event->setWishlist($wishlist)->setProduct($wishlistItem->getProduct())->setItem($wishlistItem);
            $object = $this->_objectManager->create('Magento\Framework\Event\Observer');
            $object->setEvent($event);
            $mWishlistObserver->execute($object);
            
            $this->messageManager->addSuccessMessage(__(
                "%1 has been moved to your wish list.",
                $this->escaper->escapeHtml($item->getProduct()->getName())
            ));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t move the item to the wish list.'));
        }
        return $resultRedirect->setUrl($this->cartHelper->getCartUrl());
    }
    
    public function getDataHelper() {
        return \Magento\Framework\App\ObjectManager::getInstance()->get('Itoris\MultipleWishlists\Helper\Data');
    }
}
