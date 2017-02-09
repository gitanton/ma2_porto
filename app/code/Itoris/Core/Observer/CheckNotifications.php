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
 * @package    ITORIS_M2_CORE
 * @copyright  Copyright (c) 2016 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */
namespace Itoris\Core\Observer;

use Magento\Framework\Event\ObserverInterface;

class CheckNotifications implements ObserverInterface
{
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_backendAuthSession = $backendAuthSession;
		$this->_objectManager = $objectManager;
		$this->messageManager = $messageManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_backendAuthSession->isLoggedIn()) {
            $feedModel = $this->_objectManager->create('\Itoris\Core\Model\Feed');
			$feedModel->moduleList = $this->_objectManager->create('\Magento\Framework\Module\ModuleList');
			$feedModel->_objectManager = $this->_objectManager;
			$feedModel->_messageManager = $this->messageManager;
            $feedModel->checkUpdate();
			$feedModel->notify();
        }
    }
}