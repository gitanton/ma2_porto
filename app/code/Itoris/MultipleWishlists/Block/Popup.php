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
 
namespace Itoris\MultipleWishlists\Block;

class Popup extends \Magento\Backend\Block\Widget\Container
{
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\ConfigInterface $backendConfig,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_objectManager = $objectManager;
        $this->backendConfig = $backendConfig;
        $this->_request = $objectManager->get('Magento\Framework\App\RequestInterface');
        parent::__construct($context, $data);
    }
    
    public function getDataHelper() {
        return $this->_objectManager->get('Itoris\MultipleWishlists\Helper\Data');
    }
    
    public function getWishlists() {
        if ($this->_request->getRouteName() != 'mwishlist' && $this->_request->getRouteName() != 'wishlist') {
            return [0 => (object)['id' => 0, 'name' => __('Main'), 'qty' => 0, 'active' => 1]];
        }
        
        $mWishlistCollection = $this->_objectManager->create('Itoris\MultipleWishlists\Model\Wishlist')
                                ->getCollection()
                                ->addCustomerFilter();
        $mainWishlist = $mWishlistCollection->getMainWishlist();
        $wishlists = [0 => (object)['id' => 0, 'name' => $mainWishlist->getTitle(), 'qty' => count($mainWishlist->getItems()), 'active' => 1]];
        foreach($mWishlistCollection as $mWishlist) {
            $wishlists[$mWishlist->getMwishlistId()] = (object)['id' => $mWishlist->getId(), 'name' => $mWishlist->getTitle(), 'qty' => count($mWishlist->getItems()), 'active' => $mWishlist->getIsEditable()];
        }
        return $wishlists;
    }
    
    public function isLoggedIn() {
        return $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomerId() > 0 ? 1 : 0;
    }
    
    public function getConfig() {    
        return [
            'isLoggedIn' => $this->isLoggedIn(),
            'afterWishlistSelected' => (int) $this->backendConfig->getValue('itoris_multiplewishlists/general/after_wishlist_selected', 0),
            'afterAddedToCart' => (int) $this->backendConfig->getValue('itoris_multiplewishlists/general/after_add_to_cart', 0),
            'getWishlistsUrl' => $this->getUrl('mwishlist/index/getwishlists')
        ];
    }
    
    public function toHtml(){
        return $this->getDataHelper()->isEnabled() ? parent::toHtml() : '';
    }
    
}