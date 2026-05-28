# Vendor_ManualComplete

Magento 2 admin module for manually completing a virtual/downloadable order after an offline payment.

## Module location

Place the module in the project-local code directory:

`app/code/Vendor/ManualComplete`

The module registration name is `Vendor_ManualComplete`, so Magento can load it from the `app/code/Vendor` folder requested for this project.

## Button placement

The safest place for the **Complete** button is the admin order view header:

`Sales > Orders > View Order`

This location is better than the order grid because the administrator can review the customer, items, totals, and payment context immediately before creating an offline invoice and closing the order lifecycle.

## What the action does

When an authorized administrator clicks **Complete**, the module:

1. Allows the action only for orders that can still be invoiced.
2. Allows the action only for virtual/downloadable orders, so physical/shippable orders are not accidentally closed without shipment.
3. Creates an invoice for the order.
4. Captures the invoice offline, so Magento treats the external payment as paid.
5. Saves the invoice and order in one transaction.
6. Sends the invoice email, which allows the standard digital/downloadable product delivery flow or connected key-delivery integrations to run from the invoice/order state change.
7. Adds an internal order history comment that is not visible to the customer.
8. Moves the order to the `complete` state when there is nothing left to invoice or ship.

The button is hidden for canceled, closed, already complete, non-virtual, or non-invoiceable orders and requires the custom ACL permission **Manually Complete Orders**.


## Status visibility and false positives

The button is not shown by a hard-coded list of order status labels. Instead, the UI calls the same eligibility rule as the server-side completion service: the order must be virtual/downloadable and Magento must report that it can still be invoiced.

This means states such as payment review, holded, canceled, complete, and closed are filtered out when Magento reports them as non-invoiceable. States such as pending, pending payment, or processing can still be valid candidates if Magento allows invoicing, because this action is intended for an authorized administrator who has already verified the offline payment before creating the offline invoice.
