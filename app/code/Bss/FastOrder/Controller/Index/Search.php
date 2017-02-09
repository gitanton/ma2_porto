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

use \Magento\Catalog\Helper\Image;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

class Search extends \Magento\Framework\App\Action\Action
{
    protected $imageHelper;

    protected $priceCurrency;

    protected $storeManager;

    protected $helperBss;

    protected $save;

    protected $cache;

    protected $pricingHelper;

    protected $catalogModelProduct;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Catalog\Model\Product $catalogModelProduct,
        \Bss\FastOrder\Helper\Data $helperBss,
        \Bss\FastOrder\Model\Search\Save $save,
        Session $session,
        Image $imageHelper
    ) {
        parent::__construct($context);
        $this->catalogModelProduct = $catalogModelProduct;
        $this->imageHelper = $imageHelper;
        $this->priceCurrency = $priceCurrency;
        $this->_session = $session;
        $this->storeManager = $storeManager;
        $this->cache = $cache;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->pricingHelper = $pricingHelper;
        $this->helperBss = $helperBss;
        $this->save = $save;
    }

    public function execute()
    {
        if (!$this->helperBss->getConfig('enabled')) {
            return false;
        }
        $inputRes = $this->getRequest()->getParam('product');
        $sortOrder = $this->getRequest()->getParam('sort_order');
        $maxRes = 4;
        if ($this->helperBss->getConfig('max_results_show') > 0) {
            $maxRes = $this->helperBss->getConfig('max_results_show');
        }
        $data = $this->cache->load('bss_fastorder_super_search');
        $data = $data ? $data : $this->save->getProductInfo();
        $result = [];
        $i = 0;
        $dataCache = json_decode($data, true);

        foreach ($dataCache as $key => $value) {
            $productName = $value['product_name'];
            $productSku = $value['product_sku'];
            $enabled = false;
            $tierPricesList = [];
            if ($i == $maxRes) {
                break;
            }
            if ($this->helperBss->getConfig('search_by_sku')) {
                if (preg_match('/'.$inputRes.'/i', $productName) || preg_match('/'.$inputRes.'/i', $productSku)) {
                    $pattern = preg_quote($inputRes);
                    $dataCache[$key]['product_name'] = preg_replace("/($pattern)/i", '<span class="bss-highlight">$1</span>', $dataCache[$key]['product_name']);
                    $dataCache[$key]['product_sku_highlight'] = preg_replace("/($pattern)/i", '<span class="bss-highlight">$1</span>', $dataCache[$key]['product_sku']);
                    $enabled = true;
                }
            } else {
                if (preg_match('/'.$inputRes.'/i', $productName)) {
                    $pattern = preg_quote($inputRes);
                    $dataCache[$key]['product_name'] = preg_replace("/($pattern)/i", '<span class="bss-highlight">$1</span>', $dataCache[$key]['product_name']);
                    $enabled = true;
                }
            }

            if ($enabled) {
                $dataCache[$key] = $this->setDataCache($dataCache, $key);
                array_push($result, $dataCache[$key]);
                $i++;
            }
        }
        $respon = json_encode($result);
        $this->getResponse()->setBody($respon);
        return;
    }

    protected function getTierPriceQty($tierPriceQty, $productId)
    {
        $quote = $this->_session->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $productQuote = $this->productRepositoryInterface->get($item->getSku());
            if ($productQuote->getId() == $productId) {
                $tierPriceQty = $tierPriceQty - $item->getQty();
                if ($tierPriceQty < 1) {
                    $tierPriceQty = 1;
                }
            }
        }
        return $tierPriceQty;
    }

    protected function setDataCache($dataCache = null, $key = null)
    {
        $productId = $dataCache[$key]['product_id'];
        $product = $this->catalogModelProduct->load($productId);
        $productPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
        $dataCache[$key]['product_price'] = $this->pricingHelper->currency($productPrice, true, false);
        $dataCache[$key]['product_price_amount'] = $productPrice;

        if ($product->getTypeId() == 'configurable') {
            $storeId = $this->storeManager->getStore()->getId();
            $productTypeInstance = $product->getTypeInstance();
            $productTypeInstance->setStoreFilter($storeId, $product);
            $usedProducts = $productTypeInstance->getUsedProducts($product);
            $childrenList = [];
            $childrenList = $this->getChildrenList($usedProducts);
            $dataCache[$key]['tier_price_'.$productId] = $childrenList;
        } else {
            $tierPrices = [];
            $tierPrices[1] = $productPrice;
            $tierPricesList = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();

            // add tier price to data
            if (!empty($tierPricesList)) {
                foreach ($tierPricesList as $tierPrice) {
                    $tierPriceQty = $this->getTierPriceQty($tierPrice['price_qty'], $productId);
                    $tierPrices[$tierPriceQty] = $this->priceCurrency->convert($tierPrice['price']->getValue());
                }
            }
            $dataCache[$key]['tier_price_'.$productId] = $tierPrices;
        }
        return $dataCache[$key];
    }

    protected function getChildrenList($usedProducts = null)
    {
        if (empty($usedProducts)) {
            return false;
        }
        foreach ($usedProducts as $child) {
            $attributes = [];
            $tierPrices = [];
            $tierPrices[1] = $child->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
            $isSaleable = $child->isSaleable();
            if ($isSaleable) {
                $tierPricesList = $child->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
                if (!empty($tierPricesList)) {
                    foreach ($tierPricesList as $tierPrice) {
                        $tierPriceQty = $this->getTierPriceQty($tierPrice['price_qty'], $child->getId());
                        $tierPrices[$tierPriceQty] = $this->priceCurrency->convert($tierPrice['price']->getValue());
                    }
                }
            }
            $childrenList['tier_price_child_'.$child->getId()] = $tierPrices;
        }
        return $childrenList;
    }
}
