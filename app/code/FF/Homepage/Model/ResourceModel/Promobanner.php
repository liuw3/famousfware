<?php

namespace FF\Homepage\Model\ResourceModel;


class Promobanner extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * construct
     * @return void
     */
    protected function _construct()
    {
        $this->_init('homepage_promobanners', 'banner_id');
    }
}
