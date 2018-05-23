<?php
/***************************************************************************
 Extension Name : Product Label
 Extension URL  : https://www.magebees.com/product-label-extension-magento-2.html
 Copyright      : Copyright (c) 2016 MageBees, http://www.magebees.com
 Support Email  : support@magebees.com 
 ***************************************************************************/
 
namespace Magebees\Productlabel\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_storeManager;
    protected $date;
    protected $_filesystem;
    protected $_productlabelModel;
    protected $_customerSession;
    protected $_labels = null;
    protected $_sizes = [];
    
    //productlabel wide
    const MODULE_ENABLE = 'productlabel/general/enable';
    const DISPLAY_LABEL_ON_PAGES = 'productlabel/general/display_label_on';
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Image\AdapterFactory $imageFactory,
        \Magebees\Productlabel\Model\Productlabel $productlabelModel,
		\Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Request\Http $request,
        Filesystem $filesystem
       
    ) {
        $this->_storeManager = $storeManager;
        $this->_filesystem = $filesystem;
        $this->_imageFactory = $imageFactory;
        $this->_productlabelModel = $productlabelModel;
		$this->_customerSession = $customerSession;
        $this->request = $request;
        $this->date = $date;
        parent::__construct($context);
    }
    
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue($config_path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function getRequest()
    {
        return $this->request;
    }
    
    public function getCustomerGroupId()
    {
        return $this->_customerSession->getCustomerGroupId();
    }
    
     /**
      * Check whether it is single store mode
      *
      * @return bool
      */
    public function isSingleStoreMode()
    {
        return (bool)$this->_storeManager->isSingleStoreMode();
    }
    
    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore(true)->getId();
    }

    public function isProductlabelEnabled()
    {
        return $this->getConfig('productlabel/general/enable');
    }
    
    public function getConfigDisplayLabelOnArray()
    {
        $pages = $this->getConfig('productlabel/general/display_label_on');
        $display_label_on_array = explode(',', $pages);
        return $display_label_on_array;
    }
    
    //Get the image url of label
    public function getImageUrl($label)
    {
        $mediaDirectory = $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
        
        if ($label->getMode()=="prod" && $label->getProdImage()) {
            return $mediaDirectory.'prod_image/resized'.$label->getProdImage();
        } elseif ($label->getMode()=="cat" && $label->getCatImage()) {
            return $mediaDirectory.'cat_image/resized'.$label->getCatImage();
        } else {
            return false;
        }
    }
    
    public function getCurrentDate()
    {
        return $this->date->gmtDate();
    }
	
	//Get label collection
    public function getLabelCollection()
    {
        $storeId = $this->getCurrentStoreId();
        $groupId = $this->getCustomerGroupId();
        $this->_labels = $this->_productlabelModel->getCollection()
        ->addFieldToFilter('is_active', ['eq' => 1])
        ->addFieldToFilter('customer_group_ids', [['finset' => $groupId]])
        ->setOrder('sort_order', 'ASC')
        ->setOrder('label_id', 'ASC');
        
        if (!$this->isSingleStoreMode()) {
            $this->_labels->addFieldToFilter('stores', [['finset' => $storeId]]);
        }
		return $this->_labels;
       
    }
	
	public function getLabel($product, $mode = 'category')
    {
        $html = '';
        if ($this->isProductlabelEnabled()) {
            $_labels = $this->getLabelCollection();
			
            if ($_labels->getSize()) {
                $applied = false;
                foreach ($_labels as $label) {
                   	$label->setProduct($product);
                    $label->init($mode, $product);
                    if ($label->getHide() && $applied) {
                        continue;
                    }
                    if ($label->isValid()) {
						$applied = true;
                        $html .= $this->_generateHtml($label);
                    }
	            }
            }
        }
        return $html;
    }
    
	protected function _generateHtml($label)
    {
        $html = '';
        $imgUrl = $this->getImageUrl($label);
        if ($imgUrl) {
            if (empty($this->_sizes[$imgUrl])) {
                $image_info = getimagesize($imgUrl);
                $this->_sizes[$imgUrl]['h']=$image_info[1];
                $this->_sizes[$imgUrl]['w']=$image_info[0];
            }
        
            $tableClass = $label->getCssClass();
                 
            $tableStyle = '';
            $tableStyle .= 'height:' . $this->_sizes[$imgUrl]['h'] . 'px; ';
            $tableStyle .= 'width:'  . $this->_sizes[$imgUrl]['w'] . 'px; ';
            
            $tableStyle .= $this->_getPositionAdjustment($tableClass, $this->_sizes[$imgUrl]);
           
            if ($label->getMode() == 'cat') {
                $textStyle = "color:".$label->getCatTextColor().";";
                if ($label->getCatTextSize()) {
                    $textStyle = $textStyle."font-size:".$label->getCatTextSize()."px;";
                }
            } else {
                $textStyle = "color:".$label->getProdTextColor().";";
                if ($label->getProdTextSize()) {
                    $textStyle = $textStyle."font-size:".$label->getProdTextSize()."px;";
                }
            }
            
            if ($textStyle) {
                $textStyle = 'style="' . $textStyle . '"';
            }
            
            $html .=  '<div class="prodLabel ' . $tableClass . '" style ="' . $tableStyle . '">';
			
            $html .= '<div style="background: url(' . $imgUrl .') no-repeat 0 0">';
            if ($label->getText()) {
                $html .= '<span class="productlabel-txt" ' . $textStyle . '>' . $label->getText() . '</span>';
            }
            $html .= '</div>';
            $html .= '</div>';

        }
		
        return $html;
    }
    
    protected function _getPositionAdjustment($tableClass, $sizes)
    {
        $style = '';
        if ('top-center' == $tableClass) {
            $style .= 'margin-left:' . (-$sizes['w'] / 2) . 'px;';
        } elseif (false !== strpos($tableClass, 'center')) {
            $style .= 'margin-left:' . (-$sizes['w'] / 2) . 'px;';
        }
        if (false !== strpos($tableClass, 'middle')) {
            $style .= 'margin-top:'. (-$sizes['h'] / 2) .'px;';
        }
        return $style;
    }
    
    public function resizeImg($fieldName, $fileName, $width, $height)
    {
        $dir = "resized";
        if (trim($width) == "" || trim($width) < 0) {
            $width = "80";
        }
        if (trim($height) == "" || trim($height) < 0) {
            $height = "80";
        }
                
        $mediaDir = $this->_filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $mediaDir->create($fieldName);
        $mediaDir->changePermissions($fieldName, 0775);
        $fieldName = $mediaDir->getAbsolutePath($fieldName);
        $absPath = $fieldName.$fileName;
        $imageResized = $fieldName."/".$dir.$fileName;
        
        if ($width != '') {
            if (file_exists($imageResized)) {
                unlink($imageResized);
            }
            $imageResize = $this->_imageFactory->create();
            $imageResize->open($absPath);
            $imageResize->constrainOnly(true);
            $imageResize->keepTransparency(true);
            $imageResize->keepFrame(false);
            $imageResize->keepAspectRatio(true);
            $imageResize->resize($width, $height);
            $dest = $imageResized ;
            $imageResize->save($dest);
        }
        return true;
    }
}
