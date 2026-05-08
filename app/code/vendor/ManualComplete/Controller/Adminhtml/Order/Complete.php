<?php

declare(strict_types=1);

namespace vendor\ManualComplete\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use vendor\ManualComplete\Model\OrderCompleter;

class Complete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'vendor_ManualComplete::complete_order';

    public function __construct(
        Context $context,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderCompleter $orderCompleter,
        private readonly RedirectFactory $redirectFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');
        $resultRedirect = $this->redirectFactory->create();
        $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);

        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Order ID is missing.'));
            return $resultRedirect->setPath('sales/order/index');
        }

        try {
            $order = $this->orderRepository->get($orderId);
            $invoice = $this->orderCompleter->complete($order);
            $this->messageManager->addSuccessMessage(
                __('Invoice #%1 was created offline and the order was completed.', $invoice->getIncrementId())
            );
        } catch (\Throwable $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $resultRedirect;
    }
}
