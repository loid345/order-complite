<?php

declare(strict_types=1);

namespace Vendor\ManualComplete\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Vendor\ManualComplete\Model\OrderCompleter;

class Complete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Vendor_ManualComplete::complete_order';

    public function __construct(
        Context $context,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderCompleter $orderCompleter,
        private readonly RedirectFactory $redirectFactory,
        private readonly LoggerInterface $logger
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
                __('Invoice #%1 was created offline. The order is moved to Complete when Magento has no remaining invoice/shipment operations.', $invoice->getIncrementId())
            );
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->critical($exception);
            $this->messageManager->addErrorMessage(
                __('The order could not be completed. Please check the logs for details.')
            );
        }

        return $resultRedirect;
    }
}
