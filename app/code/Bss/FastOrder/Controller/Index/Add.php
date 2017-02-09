<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This package designed for Magento COMMUNITY edition
 * BSS Commerce does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * BSS Commerce does not provide extension support in case of
 * incorrect edition usage.
 * =================================================================
 *
 * @category   BSS
 * @package    Bss_FastOrder
 * @author     Extension Team
 * @copyright  Copyright (c) 2015-2016 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\FastOrder\Controller\Index;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Exception\NoSuchEntityException;

class Add extends \Magento\Checkout\Controller\Cart\Add
{
    protected $helperBss;

    protected $escaper;

    protected $logger;

    protected $productRepos;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Escaper $escaper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\ProductRepository $productRepos,
        \Bss\FastOrder\Helper\Data $helperBss,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository
        );
        $this->registry = $registry;
        $this->escaper = $escaper;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->productRepos = $productRepos;
        $this->helperBss = $helperBss;
    }

    public function execute()
    {
        if (!$this->helperBss->getConfig('enabled')) {
            return false;
        }
        $productIds = $this->getRequest()->getParam('productIds');
        $qtys = $this->getRequest()->getParam('qtys');
        $fastorderSuperAttribute = $this->getRequest()->getParam('bss-fastorder-super_attribute');
        $fastorderLinks = $this->getRequest()->getParam('bss_fastorder_links');
        $fastorderSuperGroup = $this->getRequest()->getParam('bss-fastorder-super_group');
        $fastorderCustomOption = $this->getRequest()->getParam('bss-fastorder-options');
        $result = [];
        $success = false;
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $productNames = null;
            foreach ($productIds as $key => $productId) {
                if ($qtys[$key] <= 0 || !$productId) {
                    continue;
                }
                $params = [];
                $this->registry->unregister('row_product');
                $this->registry->register('row_product', $key);
                $product = $this->productRepos->getById($productId);
                $params = $this->addOptionProduct($params, $product, $fastorderSuperAttribute, $fastorderLinks, $fastorderSuperGroup, $key);
                // add custom option
                $params['options'] = $this->addCustomOption($fastorderCustomOption, $key);
                $params['qty'] = (int)$qtys[$key];
                $productNames .= ','.$product->getName().'&nbsp;';
                $this->cart->addProduct($product, $params);
                $success = true;
            }
            if ($success) {
                $productName = ltrim($productNames, ',');
                $this->cart->save();
                $result['status'] = true;
                $message = __(
                    'You added %1 to your shopping cart.',
                    $productName
                    );
                $this->messageManager->addSuccessMessage($message);
            } else {
                $result['status'] = false;
                $this->messageManager->addError(
                    __('Please insert product(s).')
                );
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNotice(
                    $this->escaper->escapeHtml($e->getMessage())
                );
                $result['status'] = false;
                $result['row'] = $this->registry->registry('row_product');
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addError(
                        $this->escaper->escapeHtml($message)
                    );
                }
                $result['status'] = false;
                $result['row'] = $this->registry->registry('row_product');
            }
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
            $this->logger->critical($e);
            $result['status'] = false;
            $result['row'] = $this->registry->registry('row_product');
        }
        $respon = json_encode($result);
        $this->getResponse()->setBody(($respon));
        return;
    }

    protected function addOptionProduct($params, $product = null, $fastorderSuperAttribute = null, $fastorderLinks = null, $fastorderSuperGroup = null, $key = null)
    {
        if ($product->getTypeId() == 'configurable' && !empty($fastorderSuperAttribute)) {
            $params['super_attribute'] = $fastorderSuperAttribute[$key];
        } elseif ($product->getTypeId() == 'downloadable' && !empty($fastorderLinks)) {
            $params['links'] = $fastorderLinks[$key];
        } elseif ($product->getTypeId() == 'grouped' && !empty($fastorderSuperGroup)) {
            $params['super_group'] = $fastorderSuperGroup[$key];
        }
        if (!empty($params)) {
            return $params;
        }
        return false;
    }

    protected function addCustomOption($fastorderCustomOption = null, $key = null)
    {
        if (isset($fastorderCustomOption[$key])) {
            foreach ($fastorderCustomOption[$key] as $id => $value) {
                if (is_array($value)) {
                    continue;
                }
                $valueArr = explode(',', $value);
                if (!empty($valueArr)) {
                    $newValue = rtrim($value, ',');
                    $fastorderCustomOption[$key][$id] = explode(',', $newValue);
                };
            };
            return $fastorderCustomOption[$key];
        }
        return false;
    }
}
