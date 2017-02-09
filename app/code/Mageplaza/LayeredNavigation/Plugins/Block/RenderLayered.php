<?php
/**
 *                     Mageplaza_LayeredNavigation extension
 *                     NOTICE OF LICENSE
 *
 *                     This source file is subject to the Mageplaza License
 *                     that is bundled with this package in the file LICENSE.txt.
 *                     It is also available through the world-wide-web at this URL:
 *                     https://www.mageplaza.com/LICENSE.txt
 *
 *                     @category  Mageplaza
 *                     @package   Mageplaza_LayeredNavigation
 *                     @copyright Copyright (c) 2016
 *                     @license   https://www.mageplaza.com/LICENSE.txt
 */
namespace Mageplaza\LayeredNavigation\Plugins\Block;

class RenderLayered
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
	protected $_url;

    /**
     * @var \Magento\Theme\Block\Html\Pager
     */
	protected $_htmlPagerBlock;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
	protected $_request;

    /**
     * @var \Mageplaza\LayeredNavigation\Helper\Data
     */
	protected $_moduleHelper;

    /**
     * RenderLayered constructor.
     *
     * @param \Magento\Framework\UrlInterface          $url
     * @param \Magento\Theme\Block\Html\Pager          $htmlPagerBlock
     * @param \Magento\Framework\App\RequestInterface  $request
     * @param \Mageplaza\LayeredNavigation\Helper\Data $moduleHelper
     */
	public function __construct(
		\Magento\Framework\UrlInterface $url,
		\Magento\Theme\Block\Html\Pager $htmlPagerBlock,
		\Magento\Framework\App\RequestInterface $request,
		\Mageplaza\LayeredNavigation\Helper\Data $moduleHelper
	) {
		$this->_url = $url;
		$this->_htmlPagerBlock = $htmlPagerBlock;
		$this->_request = $request;
		$this->_moduleHelper = $moduleHelper;
	}

    /**
     * @param \Magento\Swatches\Block\LayeredNavigation\RenderLayered $subject
     * @param                                                         $proceed
     * @param                                                         $attributeCode
     * @param                                                         $optionId
     *
     * @return string
     */
    public function aroundBuildUrl(\Magento\Swatches\Block\LayeredNavigation\RenderLayered $subject, $proceed, $attributeCode, $optionId)
    {
		if(!$this->_moduleHelper->isEnabled()){
			return $proceed();
		}

		$value = array();
		if($requestValue = $this->_request->getParam($attributeCode)){
			$value = explode(',', $requestValue);
		}
		if(!in_array($optionId, $value)) {
			$value[] = $optionId;
		}

        $query = [$attributeCode => implode(',', $value)];

        return $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
    }
}
