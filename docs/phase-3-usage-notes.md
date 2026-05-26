# Phase 3 Usage Notes - Stock Control

## Low-Stock Alerts

Configure `Reorder Quantity` and `Reorder Unit` on the product form. Low-stock alerts compare current stock in the configured reorder unit, using the unit relationship service when stock exists in related units.

## Stock Counts

Create a stock count from current stock balance rows. The expected quantity is copied when the draft is created and is not recalculated later. Draft counts can be edited and submitted. Submitted counts can be posted by an admin or stock manager.

## Variance Adjustments

Posting a stock count creates immutable `adjustment` stock ledger rows for non-zero variances. Positive variance posts `direction = in`. Negative variance posts `direction = out`. Stock balances are updated through `StockPostingService`.

## Expiry Tracking

Expiry alerts show expired and near-expiry batches that still have remaining stock. Expired stock is informational by default and is not removed automatically.

## Expired Stock Removal

Use the adjustment action from the expiry alert page to remove expired stock. The adjustment preserves the original product, batch, and unit, requires a reason, and posts an `adjustment` ledger row with `direction = out`.

## Stock Reports

Phase 3 includes stock on hand, stock movement, low-stock, expiry, and stock adjustment reports. Reports are filter-first and keep source rows traceable to products, batches, units, stock ledgers, and stock counts where applicable.
