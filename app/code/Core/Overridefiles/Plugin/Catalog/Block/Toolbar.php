<?php
namespace Core\Overridefiles\Plugin\Catalog\Block;

class Toolbar
{

    /**
    * Plugin
    *
    * @param \Magento\Catalog\Block\Product\ProductList\Toolbar $subject
    * @param \Closure $proceed
    * @param \Magento\Framework\Data\Collection $collection
    * @return \Magento\Catalog\Block\Product\ProductList\Toolbar
    */
    protected $localeDate;
        public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->productFactory = $productFactory;
        $this->visibility = $visibility;
        $this->localeDate = $localeDate;
    }
    public function aroundSetCollection(

    \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
    \Closure $proceed,

    $collection
    ) {
    $currentOrder = $subject->getCurrentOrder();
    $todayStartOfDayDate = $this->localeDate->date()->setTime(0, 0, 0)->format('Y-m-d H:i:s');
    $todayEndOfDayDate = $this->localeDate->date()->setTime(23, 59, 59)->format('Y-m-d H:i:s');
    $result = $proceed($collection);

    if ($currentOrder) {
        if ($currentOrder == 'high_to_low') {
            $subject->getCollection()->setOrder('price', 'desc');
        } elseif ($currentOrder == 'low_to_high') {
            $subject->getCollection()->setOrder('price', 'asc');
        } elseif($currentOrder == "new"){
            $subject->getCollection()->addAttributeToFilter(
            'news_from_date',
            [
                'or' => [
                    0 => ['date' => true, 'to' => $todayEndOfDayDate],
                    1 => ['is' => new \Zend_Db_Expr('null')],
                ]
            ],
            'left'
        )->addAttributeToFilter(
            'news_to_date',
            [
                'or' => [
                    0 => ['date' => true, 'from' => $todayStartOfDayDate],
                    1 => ['is' => new \Zend_Db_Expr('null')],
                ]
            ],
            'left'
        )->addAttributeToSort(
            'news_from_date',
            'desc'
        );
        }
    }

    return $result;
    }

}