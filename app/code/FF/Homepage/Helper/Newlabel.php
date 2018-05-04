<?php

namespace FF\Homepage\Helper;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Newlabel extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    public function __construct(
        TimezoneInterface $localeDate
    ) {
        $this->localeDate = $localeDate;
    }

    public function isProductNew($product)
    {
        //echo $product->getName();
        $newsFromDate = $product->getNewsFromDate();
        $newsToDate = $product->getNewsToDate();
        if (!$newsFromDate && !$newsToDate) {
            return false;
        }

        return $this->localeDate->isScopeDateInInterval(
            $product->getStore(),
            $newsFromDate,
            $newsToDate
        );
    }
}