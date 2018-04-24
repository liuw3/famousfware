<?php
namespace FF\Homepage\Model\ResourceModel\Promobanner;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'banner_id';
    protected function _construct()
    {
        $this->_init('FF\Homepage\Model\Promobanner', 'FF\Homepage\Model\ResourceModel\Promobanner');
    }
    
}