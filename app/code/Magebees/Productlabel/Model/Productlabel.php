<?php
/***************************************************************************
 Extension Name	: Product Label
 Extension URL	: https://www.magebees.com/product-label-extension-magento-2.html
 Copyright		: Copyright (c) 2016 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
 
namespace Magebees\Productlabel\Model;

class Productlabel extends \Magento\Framework\Model\AbstractModel
{
    protected $_info = [];
    protected $_date;
    protected $_catalogHelper;
	
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
	    \Magento\Catalog\Helper\Data $catalogHelper,
		\Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
		\Magebees\Productlabel\Model\RuleFactory $ruleFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_date = $date;
        $this->ruleFactory = $ruleFactory;
        $this->_catalogHelper = $catalogHelper;
		$this->priceCurrency = $priceCurrency;
        $this->coreRegistry = $registry;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }
     
     /**
      * Initialization
      *
      * @return void
      */
    protected function _construct()
    {
        $this->_init('Magebees\Productlabel\Model\ResourceModel\Productlabel');
    }
    
    public function getPosition()
    {
        return [
            [
                'value' => 'TL',
                'label' => __('Top-Left')
            ],
            [
                'value' => 'TC',
                'label' => __('Top-Center')
            ],
            [
                'value' => 'TR',
                'label' => __('Top-Right')
            ],
            [
                'value' => 'ML',
                'label' => __('Middle-Left')
            ],
            [
                'value' => 'MC',
                'label' => __('Middle-Center')
            ],
            [
                'value' => 'MR',
                'label' => __('Middle-Right')
            ],
            [
                'value' => 'BL',
                'label' => __('Bottom-Left')
            ],
            [
                'value' => 'BC',
                'label' => __('Bottom-Center')
            ],
            [
                'value' => 'BR',
                'label' => __('Bottom-Right')
            ]
        ];
    }
    
    public function validateData($object)
    {
        $fromDate = $object['from_date'];
        $toDate = $object['to_date'];
        if ($fromDate != "" && $toDate != "") {
            $date = $this->_date;
            $value = $date->timestamp($fromDate);
            $maxValue = $date->timestamp($toDate);
            if ($value > $maxValue) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }
    
    //set the product or category mode
    public function init($mode, $p)
    {
		if ($mode) {
            $this->setMode($mode == 'category' ? 'cat' : 'prod');
        }
        
    }
        
    /**
     * Get the label position
     */
    public function getCssClass()
    {
        $all = $this->getAvailablePositions(false);
        return $all[$this->getValue('position')];
    }
    
    public function getValue($val)
    {
        return $this->_getData($this->getMode() . '_' . $val);
    }
    
    public function getAvailablePositions($asText = true)
    {
        $a = [];
        foreach (['top', 'middle', 'bottom'] as $first) {
            foreach (['left', 'center', 'right'] as $second) {
                $pos = ucfirst($first[0]).ucfirst($second[0]);
                $a[$pos] = $asText ? __(ucwords($first . ' ' . $second)) : $first . '-' . $second;
            }
        }
        return $a;
    }
    
    /**
     * Get the label text
     */
    public function getText()
    {
        $p = $this->getProduct();
            
        $txt = $this->getValue('text');
        
        $vars = [];
        preg_match_all('/{([a-zA-Z:\_0-9]+)}/', $txt, $vars);
        if (!$vars[1]) {
            return $txt;
        }
        
        $vars = $vars[1];
                    
        foreach ($vars as $var) {
            $value = '';
            switch ($var) {
                case 'PRICE':
					$price = $this->_getPrices();
                    $value = $this->getFormatedPrice($price['price']);
                    break;
                case 'SPECIAL_PRICE':
					$price = $this->_getPrices();
                    $value = $this->getFormatedPrice($price['special_price']);
                    break;
                case 'FINAL_PRICE':
					 $value = $this->getFormatedPrice(
                        $this->_catalogHelper->getTaxPrice($p, $p->getFinalPrice(), false)
                    );
                    break;
                case 'FINAL_PRICE_INCL_TAX':
                    $value = $this->getFormatedPrice(
                        $this->_catalogHelper->getTaxPrice($p, $p->getFinalPrice(), true)
                    );
                    break;
                
                case 'SAVE_AMOUNT':
					$price = $this->_getPrices();
                    $value = $this->getFormatedPrice($price['price'] - $price['special_price']);
                    break;
                
                case 'SAVE_PERCENT':
					$value = 0;
                    $price = $this->_getPrices();
                    if ($price['price']) {
                        $value = $price['price'] - $price['special_price'];
                        $value = round($value * 100 / $price['price']);
                    }
					break;
                
                case 'BR':
                    $value = '<br/>';
                    break;
                
                case 'SKU':
                    $value = $p->getSku();
                    break;
                
                case 'NEW_FOR':
                    $createdAt = strtotime($p->getCreatedAt());
                    $value = max(1, floor((time() - $createdAt) / 86400));
                    break;
            }
            $txt = str_replace('{' . $var . '}', $value, $txt);
        }
        return $txt;
    }
    
    public function isValid()
    {
        $p = $this->getProduct();
        
		$condSerialize = $isMatch = false;
        if ("" != $this->getData('cond_serialize')) {
            $condSerialize = true;

            /** @var \Magebees\Productlabel\Model\Rule $ruleModel */
            $ruleModel = $this->ruleFactory->create();
            $ruleModel->setConditions([]);
            $ruleModel->setConditionsSerialized($this->getData('cond_serialize'));
            $ruleModel->setProduct($p);

            $productIds = $ruleModel->getMatchingProductIds();
            $isMatch = array_key_exists($p->getId(), $productIds);
        	    
        }
		
		//check for new product
        if ($this->getIsNew()) {
            $isNew = $this->isNew($p) ? 2 : 1;
            if ($this->getIsNew() != $isNew) {
                return false;
            }
        }
        
        //check for on sale product
        if ($this->getIsSale()) {
            $isSale = $this->isSale($p) ? 2 : 1;
            if ($this->getIsSale() != $isSale) {
                return false;
            }
        }
                
        //check date range
        $now = $this->_date->gmtDate();
        if ($this->getDateEnabled() && ($now < $this->getFromDate() || $now > $this->getToDate())) {
            return false;
        }
        
        //check price range
        if ($this->getPriceEnabled()) {
			switch ($this->getByPrice()) {
                case '0': // Base Price
                    $price = $p->getPrice();
                    break;
                case '1': // Special Price
                    $price = $p->getSpecialPrice();
                    break;
                case '2': // Final Price
                    $price = $this->_catalogHelper->getTaxPrice($p, $p->getFinalPrice(), false);
                    break;
                case '3': // Final Price Incl Tax
                    $price = $this->_catalogHelper->getTaxPrice($p, $p->getFinalPrice(), true);
                    break;
            }
			
            if ($p->getTypeId() == 'bundle') {
				$minimalPrice = $this->_catalogHelper->getTaxPrice($p, $p->getData('min_price'), true);
				$maximalPrice = $this->_catalogHelper->getTaxPrice($p, $p->getData('max_price'), true);
				if ($minimalPrice < $this->getFromPrice() && $maximalPrice > $this->getToPrice()) {
					return false;
				}
			} elseif ($price < $this->getFromPrice() || $price > $this->getToPrice()) {
				return false;
			}
        }
        
        //check stock status
        $stockStatus = $this->getStockStatus();
        if ($stockStatus) {
            $inStock = $p->isSalable() ? 1 : 2;
            if ($inStock != $stockStatus) {
                return false;
            }
        }
        
		return ($condSerialize && $isMatch) || !$condSerialize;
    }
    
    public function isNew($p)
    {
        $fromDate = '';
        $toDate   = '';
        $fromDate = $p->getNewsFromDate();
        $toDate   = $p->getNewsToDate();
        if ($fromDate) {
            if (time() < strtotime($fromDate)) {
                return false;
            }
            if ($toDate) {
                if (time() > strtotime($toDate)) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }
        
    public function isSale($p)
    {
		$price = $this->_getPrices();
        if ($price['price'] <= 0 || !$price['special_price']) {
            return false;
        }

        $discount_diff = $price['price'] - $price['special_price'];
       
        if ($discount_diff < 0.001 ) {
            return false;
        }
     
        return true;
		
        
    }
	
	protected function _getPrices()
    {
        if (!$this->_info) {
            /** @var \Magento\Catalog\Model\Product $p */
            $p = $this->getProduct();
            
            
			
            //$regularPrice = $p->getPriceInfo()->getPrice('regular_price')->getValue();
			$regularPrice = $p->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();

            $specialPrice = 0;
			
			if($this->getIsSale()){
				$now = $this->_date->date('Y-m-d 00:00:00');
				if ($p->getSpecialFromDate() && $now >= $p->getSpecialFromDate()){
					$specialPrice = $p->getPriceInfo()->getPrice('special_price')->getAmount()->getValue();
					//$specialPrice = $p->getData('special_price');
					if ($p->getSpecialToDate() && $now > $p->getSpecialToDate()) {
                        $specialPrice = 0;
                    }
					$finalPrice = $p->getPriceModel()->getFinalPrice(null, $p);
					if($finalPrice < $specialPrice){
						$specialPrice = $finalPrice;
					}
					
				} else {
					$specialPrice = $p->getPriceModel()->getFinalPrice(null, $p);
				}
				
				if ($p->getTypeId() == 'bundle') {
                    $specialPrice = $p->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
                }
			}
			
			if ($p->getTypeId() == 'grouped') {
				$asso_prod = $p->getTypeInstance(true)->getAssociatedProducts($p);
				foreach ($asso_prod as $child) {
					if ($child->getId() != $p->getId()) {
						$regularPrice += $child->getPrice();
						$specialPrice += $child->getFinalPrice();
					}
				}
			}
			
      		$this->_info = [
				'price' => $regularPrice,
				'special_price' => $specialPrice
			];
        }
        return $this->_info;
    }
	
	public function getFormatedPrice($amount)
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }
}
