<?php

declare(strict_types=1);

namespace Vendor\ManualComplete\Plugin\Adminhtml;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Model\Order;

class OrderViewButton
{
    private const ACL_RESOURCE = 'Vendor_ManualComplete::complete_order';

    public function __construct(
        private readonly AuthorizationInterface $authorization,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper
    ) {
    }

    public function beforeSetLayout(View $subject): void
    {
        $order = $subject->getOrder();

        if (!$order instanceof Order || !$this->canShowButton($order)) {
            return;
        }

        $message = __('Create an offline invoice, mark this order as complete, and send the digital key email?');
        $url = $this->urlBuilder->getUrl('ordercomplete/order/complete', [
            'order_id' => (int)$order->getEntityId(),
        ]);

        $subject->addButton(
            'ordercomplete_manual_complete',
            [
                'label' => __('Complete Order'),
                'class' => 'complete primary',
                'onclick' => sprintf(
                    "if (confirm('%s')) { var form = document.createElement('form'); form.method = 'post'; form.action = '%s'; var key = document.createElement('input'); key.type = 'hidden'; key.name = 'form_key'; key.value = window.FORM_KEY; form.appendChild(key); document.body.appendChild(form); form.submit(); }",
                    $this->escaper->escapeJs((string)$message),
                    $this->escaper->escapeJs($url)
                ),
            ],
            0,
            100,
            'header'
        );
    }

    private function canShowButton(Order $order): bool
    {
        if (!$this->authorization->isAllowed(self::ACL_RESOURCE)) {
            return false;
        }

        if ($order->isCanceled() || $order->getState() === Order::STATE_CLOSED) {
            return false;
        }

        if ($order->getState() === Order::STATE_COMPLETE) {
            return false;
        }

        if (!(bool)$order->getIsVirtual()) {
            return false;
        }

        return $order->canInvoice() || $order->getState() === Order::STATE_PAYMENT_REVIEW;
    }
}
