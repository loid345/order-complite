# OrderComplite ManualComplete

Magento 2 admin module for manually completing an order after an offline payment.

## Button placement

The safest place for the **Complete** button is the admin order view header:

`Sales > Orders > View Order`

This location is better than the order grid because the administrator can review the customer, items, totals, and payment context immediately before creating an offline invoice and closing the order lifecycle.

## What the action does

When an authorized administrator clicks **Complete**, the module:

1. Creates an invoice for the order.
2. Captures the invoice offline, so Magento treats the external payment as paid.
3. Saves the invoice and order in one transaction.
4. Sends the invoice email, which allows the standard digital/downloadable product delivery flow or connected key-delivery integrations to run from the invoice/order state change.
5. Adds a visible order history comment.

The button is hidden for canceled, closed, or already complete orders and requires the custom ACL permission **Manually Complete Orders**.
