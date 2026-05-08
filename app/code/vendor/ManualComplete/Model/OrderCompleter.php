<?php

declare(strict_types=1);

namespace vendor\ManualComplete\Model;

use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;

class OrderCompleter
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InvoiceSender $invoiceSender,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TransactionFactory $transactionFactory,
        private readonly OrderConfig $orderConfig
    ) {
    }

    public function complete(Order $order): Invoice
    {
        if (!$order->canInvoice()) {
            throw new \DomainException((string)__('The order cannot be invoiced.'));
        }

        $invoice = $this->invoiceService->prepareInvoice($order);

        if (!$invoice->getTotalQty()) {
            throw new \DomainException((string)__('The invoice cannot be created without products.'));
        }

        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        $invoice->getOrder()->setCustomerNoteNotify(false);
        $invoice->getOrder()->setIsInProcess(true);

        $transaction = $this->transactionFactory->create();
        $transaction->addObject($invoice);
        $transaction->addObject($invoice->getOrder());
        $transaction->save();

        $this->invoiceSender->send($invoice);

        $order = $invoice->getOrder();

        if (!$order->canInvoice() && !$order->canShip()) {
            $order->setState(Order::STATE_COMPLETE);
            $order->setStatus($this->orderConfig->getStateDefaultStatus(Order::STATE_COMPLETE));
        }

        $order->addCommentToStatusHistory(
            (string)__('Order manually completed after offline payment. Invoice email was sent.'),
            false,
            true
        );
        $this->orderRepository->save($order);

        return $invoice;
    }
}
