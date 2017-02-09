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

class Getwishlists extends \Magento\Framework\App\Action\Action
{

    public function execute()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        if (!$this->getDataHelper()->isEnabled()) return;
        
        $block = $this->_objectManager->create('Itoris\MultipleWishlists\Block\Popup');
        
        header('Content-Type: application/json');
        
        $this->getResponse()->setBody(json_encode([
            'success' => 1,
            'wishlists' => $block->getWishlists(),
            'config' => $block->getConfig(),
            'popup_html' => $block->setTemplate('Itoris_MultipleWishlists::popup.phtml')->toHtml()
        ]));
        
    }
    
    public function getDataHelper() {
        return $this->_objectManager->get('Itoris\MultipleWishlists\Helper\Data');
    }
}
