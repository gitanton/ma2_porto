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
namespace Bss\FastOrder\Model\Search;

class Save
{
    protected $imageHelper;
    protected $cache;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Catalog\Helper\Image $imageHelper
    ) {
        $this->cache = $cache;
        $this->imageHelper = $imageHelper;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
    }

    public function getProductInfo()
    {
        $image = 'category_page_grid';
        $imageSize = 1280;
        $collection = $this->_productCollectionFactory->create();
        $collection ->addAttributeToSelect('*')
                    ->addStoreFilter()
                    ->addUrlRewrite()
                    ->addAttributeToFilter('type_id', ['neq' => 'bundle'])
                    ->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()])
                    ->setVisibility($this->productVisibility->getVisibleInSiteIds())
                    ->load();
        $dataCache = [];
        foreach ($collection as $product) {
            $showPopup = 0;
            $productUrl = $product->getUrlModel()->getUrl($product);
            $productThumbnail = $this->imageHelper->init($product, $image, ['height' => $imageSize , 'width'=> $imageSize])->getUrl();
            if ($product->getHasOptions()) {
                $showPopup = 1;
            }
            if ($product->getTypeId() == 'configurable' || $product->getTypeId() == 'grouped') {
                $showPopup = 1;
            }
            if ($product->getTypeId() == 'downloadable' && $product->getTypeInstance()->getLinkSelectionRequired($product)) {
                $showPopup = 1;
            }
            if ($product->getTypeId() == 'bundle') {
                $showPopup = 1;
            }
            $dataCache[] =  [
                                'product_name'         => $product->getName(),
                                'product_sku'          => $product->getSku(),
                                'product_id'           => $product->getId(),
                                'product_thumbnail'    => $productThumbnail,
                                'product_url'          => $productUrl,
                                'popup'                => $showPopup
                            ];
        }
        $data = json_encode($dataCache);
        $tags = ['product_info'];
        $this->cache->save($data, 'bss_fastorder_super_search', $tags, 86400*7);
        return $data;
    }
}
