<?php

namespace Zalw\Catalog\Helper\Product;

/**
 * Class ProductList
 */
class ProductList extends \Magento\Catalog\Helper\Product\ProductList
{    
    /**
     * List mode configuration path
     */
    const VIEW_MODE_LINE = 'line';

    /**
     * Returns available mode for view
     *
     * @return array|null
     */
    public function getAvailableViewMode()
    {
        switch ($this->scopeConfig->getValue(self::XML_PATH_LIST_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            case 'grid':
                $availableMode = ['grid' => __('Grid')];
                break;

            case 'list':
                $availableMode = ['list' => __('List')];
                break;

            case 'line':
                $availableMode = ['line' => __('Line')];
                break;

            case 'grid-list':
                $availableMode = ['grid' => __('Grid'), 'list' =>  __('List'),'line' => __('line')];
                break;

            case 'list-grid':
                $availableMode = ['list' => __('List'), 'grid' => __('Grid'),'line' => __('line')];
                break;

            case 'line-grid':
                $availableMode = ['line' => __('line'), 'grid' => __('Grid'),'list' =>  __('List')];
                break;

            default:
                $availableMode = null;
                break;
        }
        return $availableMode;
    }

    /**
     * Retrieve available limits for specified view mode
     *
     * @param string $mode
     * @return array
     */
    public function getAvailableLimit($mode)
    {
        if (!in_array($mode, [self::VIEW_MODE_GRID, self::VIEW_MODE_LIST, self::VIEW_MODE_LINE])) {
            return $this->_defaultAvailableLimit;
        }
        $perPageConfigKey = 'catalog/frontend/' . $mode . '_per_page_values';
        $perPageValues = (string)$this->scopeConfig->getValue(
            $perPageConfigKey,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $perPageValues = explode(',', $perPageValues);
        $perPageValues = array_combine($perPageValues, $perPageValues);
        if ($this->scopeConfig->isSetFlag(
            'catalog/frontend/list_allow_all',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            return ($perPageValues + ['all' => __('All')]);
        } else {
            return $perPageValues;
        }
    }

    /**
     * Retrieve default per page values
     *
     * @param string $viewMode
     * @return string (comma separated)
     */
    public function getDefaultLimitPerPageValue($viewMode)
    {
        if ($viewMode == self::VIEW_MODE_LIST) {
            return $this->scopeConfig->getValue(
                'catalog/frontend/list_per_page',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }if ($viewMode == self::VIEW_MODE_LINE) {
            return $this->scopeConfig->getValue(
                'catalog/frontend/list_per_page',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }elseif ($viewMode == self::VIEW_MODE_GRID) {
            return $this->scopeConfig->getValue(
                'catalog/frontend/list_per_page',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return 0;
    }
    
}
?>