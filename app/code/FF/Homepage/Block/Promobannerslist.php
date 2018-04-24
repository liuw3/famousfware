<?php

namespace FF\Homepage\Block;

use Magento\Framework\View\Element\Template;

class Promobannerslist extends Template
{
    protected $_bannersCollection;
    protected $_storeManager;
    

    public function __construct(
        Template\Context $context,
        \FF\Homepage\Model\ResourceModel\Promobanner\CollectionFactory $_bannersCollection
        
    ){
        $this->_storeManager = $context->getStoreManager();
        $this->_bannersCollection = $_bannersCollection;
        parent::__construct($context);
    }
    public function getPromotionalbanners(){
        $collection = $this->_bannersCollection->create();
        $collection->addFieldToFilter('is_active',1);
        $collection->setOrder('sort_order','ASC');
        return $collection;
    }
    
       
     
    
}   