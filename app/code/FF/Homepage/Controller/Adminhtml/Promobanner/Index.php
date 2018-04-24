<?php
namespace FF\Homepage\Controller\Adminhtml\Promobanner;
 
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action;
 
class Index extends Action
{
    const ADMIN_RESOURCE = 'FF_Homepage::promobanner';
 
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
 
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }
 
    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('FF_Homepage::ff_head');
        $resultPage->addBreadcrumb(__('Homepage - Promotional Banners'), __('Homepage - Promotional Banners'));
        $resultPage->addBreadcrumb(__('Manage Promotional Banners'), __('Manage Promotional Banners'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Promotional Banners'));
 
        return $resultPage;
    }
}