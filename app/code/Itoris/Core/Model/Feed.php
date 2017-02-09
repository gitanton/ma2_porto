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
namespace Itoris\Core\Model;

use Magento\Framework\Config\ConfigOptionsListConstants;

class Feed extends \Magento\AdminNotification\Model\Feed
{
    const XML_USE_HTTPS_PATH = 'system/itorisnotification/use_https';

    const XML_FEED_URL_PATH = 'system/itorisnotification/feed_url';

    const XML_FREQUENCY_PATH = 'system/itorisnotification/frequency';

    const XML_LAST_UPDATE_PATH = 'system/itorisnotification/last_update';
	
	private $_installedModules = array();
	
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\ConfigInterface $backendConfig,
        \Magento\AdminNotification\Model\InboxFactory $inboxFactory,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $backendConfig, $inboxFactory, $curlFactory, $deploymentConfig, $productMetadata, $urlBuilder, $resource, $resourceCollection, $data);
        $this->_backendConfig    = $backendConfig;
        $this->_inboxFactory     = $inboxFactory;
        $this->curlFactory       = $curlFactory;
        $this->_deploymentConfig = $deploymentConfig;
        $this->productMetadata   = $productMetadata;
        $this->urlBuilder        = $urlBuilder;
    }
	
    public function getFeedUrl()
    {
        $httpPath = $this->_backendConfig->isSetFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://';
        if ($this->_feedUrl === null) {
            $this->_feedUrl = $httpPath . $this->_backendConfig->getValue(self::XML_FEED_URL_PATH);
        }
        return $this->_feedUrl;
    }
	
    public function checkUpdate()
    {
        if ($this->getFrequency() + $this->getLastUpdate() > time()) {
            return $this;
        }

        $feedData = [];
		
        $feedXml = $this->getFeedData();

        $installDate = strtotime($this->_deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_INSTALL_DATE));

        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
                $itemPublicationDate = strtotime((string)$item->pubDate);
                if ($installDate <= $itemPublicationDate) {
                    $feedData[] = [
                        'severity' => (int)$item->severity,
                        'date_added' => date('Y-m-d H:i:s', $itemPublicationDate),
                        'title' => (string)$item->title,
                        'description' => (string)$item->description,
                        'url' => (string)$item->link,
                    ];
                }
            }

            if ($feedData) {
                $this->_inboxFactory->create()->parse(array_reverse($feedData));
            }
        }
		if ($feedXml && $feedXml->channel && $feedXml->channel->failedlicense) {
			foreach ($feedXml->channel->failedlicense as $item) {
				$module = (string) $item;
				$this->setConfigValue('itoris_core/installed/'.$module, 'no license');
			}
		}
		
        $this->setLastUpdate();

        return $this;
    }
    public function getFrequency()
    {
        return $this->_backendConfig->getValue(self::XML_FREQUENCY_PATH) * 3600;
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->_cacheManager->load('itoris_notifications_lastcheck');
    }

    /**
     * Set last update time (now)
     *
     * @return $this
     */
    public function setLastUpdate()
    {
        $this->_cacheManager->save(time(), 'itoris_notifications_lastcheck');
        return $this;
    }

    /**
     * Retrieve feed data as XML element
     *
     * @return \SimpleXMLElement
     */
    public function getFeedData()
    {
		$modulesInstalled = $this->getItorisModulesInstalled();
        $curl = $this->curlFactory->create();
		$feedData = implode('&', $modulesInstalled);
		$feeds = array();
		if ((int) $this->_backendConfig->getValue('itoris_core/notifications/updates')) $feeds[] = 'updates';
		if ((int) $this->_backendConfig->getValue('itoris_core/notifications/newext')) $feeds[] = 'newext';
		if ((int) $this->_backendConfig->getValue('itoris_core/notifications/news')) $feeds[] = 'news';
		$feedData .= '&feeds='.implode(',', $feeds);
        $curl->setConfig(
            [
                'timeout'   => 2,
                'useragent' => $this->productMetadata->getName()
                    . '/' . $this->productMetadata->getVersion()
                    . ' (' . $this->productMetadata->getEdition() . ')',
                'referer'   => $this->getBase()
            ]
        );
        $curl->write(\Zend_Http_Client::POST, $this->getFeedUrl(), '1.0', array(), $feedData);
        $data = $curl->read();
        if ($data === false) {
            return false;
        }
        $data = preg_split('/^\r?$/m', $data, 2);
        $data = trim($data[1]);
        $curl->close();

        try {
            $xml = new \SimpleXMLElement($data);
        } catch (\Exception $e) {
            return false;
        }

        return $xml;
    }
	
	private function getBase() {
		$base = strtolower($this->_backendConfig->getValue('web/unsecure/base_url'));
		$base = str_replace(array('http://www.', 'http://', 'https://www.', 'https://'), '', $base);
		if (substr($base, -1) == '/') $base = substr($base, 0, strlen($base) - 1);
		return $base;
	}
	
    /**
     * Retrieve feed as XML element
     *
     * @return \SimpleXMLElement
     */
    public function getFeedXml()
    {
        try {
            $data = $this->getFeedData();
            $xml = new \SimpleXMLElement($data);
        } catch (\Exception $e) {
            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?>');
        }

        return $xml;
    }
	
	private function getItorisModulesInstalled(){
		if (count($this->_installedModules)) return $this->_installedModules;
		$modules = array();
		foreach($this->_deploymentConfig->get('modules') as $module => $isActive){
			if ($isActive && strtolower(substr($module, 0, 6)) == 'itoris') {
				$data = $this->_backendConfig->getValue('itoris_core/installed/'.$module);
				$modules[] = 'module['.$module.']='.urlencode($this->moduleList->getOne($module)['setup_version'].'|'.$data);
			}
		}
		$this->_installedModules = $modules;
		return $modules;
	}
	
	private function setConfigValue($path, $value) {
        $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection('write');
		$connection->query("update {$resource->getTableName('core_config_data')} set `value`='{$value}' where `path`='{$path}'");
	}
	
	private function getConfigValue($path) {
        $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection('write');
		$configData = $connection->fetchRow("select * from {$resource->getTableName('core_config_data')} where `path`='{$path}'");
		if ((int)$configData['config_id'] && ($configData['scope'] != 'default' || (int)$configData['scope_id'] != 0)) {
			$connection->query("delete from {$resource->getTableName('core_config_data')} where `path`='{$path}'");
			$connection->query("insert into {$resource->getTableName('core_config_data')} set `config_id`={$configData['config_id']}, `scope`='default', `scope_id`=0, `path`='{$configData['path']}', `value`='{$configData['value']}'");
		}
		return $configData['value'];
	}
	
	public function notify(){
		$installedModules = $this->getItorisModulesInstalled();
		foreach($this->_deploymentConfig->get('modules') as $module => $isActive){
			if ($isActive && strtolower(substr($module, 0, 6)) == 'itoris' && $module != 'Itoris_Core') {
				$data = '';
				$data = @$this->getConfigValue('itoris_core/installed/'.$module);
				if (count(explode('|', $data)) != 2) $this->_messageManager->addError(__('Module '.$module.' is not registered! Go to <b>Stores -> Configuration -> ITORIS EXTENSIONS -> General Settings</b> to register the module'));
			}
		}
	}
}
