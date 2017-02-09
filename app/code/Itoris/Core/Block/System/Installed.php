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
namespace Itoris\Core\Block\System;

class Installed extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    protected $_moduleList;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
		\Magento\Backend\App\ConfigInterface $backendConfig,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
		\Magento\Framework\App\ProductMetadataInterface $productMetadata,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->_moduleList = $moduleList;
		$this->_objectManager = $objectManager;
		$this->_backendConfig = $backendConfig;
		$this->curlFactory       = $curlFactory;
		$this->productMetadata   = $productMetadata;
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);

        $modules = array();
		foreach($this->_moduleList->getNames() as $module) {
			if (strtolower(substr($module, 0, 6)) == 'itoris' && $module != 'Itoris_Core') {
				$modules[] = ['name' => $module,
							'version' => $this->_moduleList->getOne($module)['setup_version']];
			}
		}
		sort($modules);

		foreach($modules as $module) {
			$html .= $this->getModuleHtml($module);
		}
		
		$html .= $this->_getFooterHtml($element);
		
		return $html;
    }
	
	private function getModuleHtml($module) {
		$hash = $this->getConfigValue('itoris_core/installed/'.$module['name']);
		if (strlen($hash) == 24) {
			$warn = $this->tryRegister($module['name'], $hash);
			$hash = $this->getConfigValue('itoris_core/installed/'.$module['name']);
		} else if (strpos($hash, '|') === false && $hash != 'no license') {
			$warn = "Incorrect license!";
			$this->setConfigValue('itoris_core/installed/'.$module['name'], 'no license');
		} else {
			$warn = "";
		}
		
		$html = '<tr><td class="label" style="white-space:nowrap;"><label>'.$module['name'].'</label></td>';
		if (strpos($hash, '|') === false) {
			$html .= '<td class="value" style="vertical-align:bottom;">
							<span class="reginfo">
								'.($warn ? '<b style="color:#aa0000;">'.$warn.'</b><br />' : '').'
								<span style="color:red">Not Registered</span>
								<a href="javascript://" onclick="jQuery(this.parentNode).hide(); jQuery(this).closest(\'td\').find(\'.regnow\').show();">(Register Now?)</a>
							</span>
							<span class="regnow" style="display:none">
								<span>Enter Your License Key:</span>
								<input type="text" style="margin-top:5px;" name="groups[installed][fields]['.$module['name'].'][value]" />
								<button type="button" style="margin-top:5px;" onclick="this.disabled=true; jQuery(\'.page-actions #save\').click()"><span><span>Register</span></span></button>
							</span>
						</td>';
		} else {
			$html .= '<td class="value" style="vertical-align:bottom;">
				'.($warn ? '<b style="color:#00aa00;">'.$warn.'</b><br />' : '').'
				<span style="color:blue">Registered</span><span style="color:green; margin-left:20px;">v'.$module['version'].'</span>
			</td>';
		}
		$html .= '<td></td></tr>';
		return $html;
	}
	
    private function getFeedUrl()
    {
        $httpPath = $this->_backendConfig->isSetFlag('system/itorisnotification/use_https') ? 'https://' : 'http://';
        return $httpPath . $this->_backendConfig->getValue('system/itorisnotification/feed_url');
    }
	
	private function getBase() {
		$base = strtolower($this->_backendConfig->getValue('web/unsecure/base_url'));
		$base = str_replace(array('http://www.', 'http://', 'https://www.', 'https://'), '', $base);
		if (substr($base, -1) == '/') $base = substr($base, 0, strlen($base) - 1);
		return $base;
	}
	
	private function tryRegister($module, $hash) {
		$moduleVersion = $this->_objectManager->create('\Magento\Framework\Module\ModuleList')->getOne($module)['setup_version'];
		$curl = $this->curlFactory->create();
		$feedUrl = $this->getFeedUrl();
		$feedUrl .= '&task=register&license='.urlencode($hash).'&module='.urlencode($module).'&version='.urlencode($moduleVersion);
        $curl->setConfig(
            [
                'timeout'   => 2,
                'useragent' => $this->productMetadata->getName()
                    . '/' . $this->productMetadata->getVersion()
                    . ' (' . $this->productMetadata->getEdition() . ')',
                'referer'   => $this->getBase()
            ]
        );
        $curl->write(\Zend_Http_Client::GET, $feedUrl, '1.0');
        $data = $curl->read();
        if ($data === false) {
            return 'Cannot connect the update server';
        }

        $data = preg_split('/^\r?$/m', $data, 2);
        $data = trim($data[1]);
        $curl->close();

        try {
            $xml = new \SimpleXMLElement($data);
        } catch (\Exception $e) {
            return 'Cannot validate the license';
        }
		
		$error = (int) @$xml->error;
		$message = (string) @$xml->message;
		$hash = (string) @$xml->hash;
		
		if ($error) {
			$this->setConfigValue('itoris_core/installed/'.$module, 'no license');
			return $message;
		}
		$this->setConfigValue('itoris_core/installed/'.$module, $hash);
		return $message;
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
}