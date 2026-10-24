# Usage Guide

Day-to-day workflows for the POS System, organized around the features in
[features.md](features.md). For setup, see [installation.md](installation.md); if something
breaks, see [troubleshooting.md](troubleshooting.md).

## Signing in

Log in with your assigned account. Access is role-based (see
[User management](#user-management)): cashiers work the sales flow, while administrators
also manage inventory, users, and reports.

## Making a sale

The core workflow, built for fast checkout:

1. Start a new sale and add items to the ticket.
2. Adjust quantities as needed; stock levels are tracked in real time, so what you can
   sell reflects what is actually on hand.
3. Complete the sale — an invoice is generated dynamically for the transaction.
4. Hand the invoice to the customer; the sale is recorded for reporting.

## Working with inventory

- **Check stock** before promising an item: inventory tracking is real time, so the level
  you see is current.
- **Watch low-stock alerts:** items running low are flagged by the system. Review them
  during the day so reordering happens before you run out at the counter.
- **After a delivery**, add the received stock so the recorded levels match the shelves;
  every later sale depends on these numbers being right.

## Running reports

Sales reports are available on three horizons:

- **Daily** — the day's takings; the standard end-of-day check.
- **Weekly** — spotting which days and items carry the week.
- **Monthly** — the overview for purchasing and planning decisions.

Reports draw on the same recorded sales as the invoicing flow, so there is nothing extra
to export or reconcile by hand.

## User management

Administrators manage who can do what through role-based access control:

- **Cashiers** — the sales and invoicing workflow.
- **Administrators** — everything cashiers can do, plus inventory management, user
  accounts, and reports.

Give each staff member their own account rather than sharing one login, so sales and
changes stay attributable to a person.

## A sensible daily rhythm

1. **Open:** sign in, glance at low-stock alerts.
2. **Trade:** run sales through the checkout flow; invoices are generated for you.
3. **Close:** run the daily sales report and compare it with the till.
