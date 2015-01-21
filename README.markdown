# Order Entries

## Installation

1. Upload the 'order_entries' folder in this archive to your Symphony 'extensions' folder.
2. Enable it by selecting the "Order Entries", choose Enable from the with-selected menu, then click Apply.
3. You can now add the "Entry Order" field to your sections.

## Usage

1. Add the "Entry Order" field to your section and tick the "Show column" box.
2. **Click the column heading to sort table** by the Entry Order field to enable drag and drop.
3. When ordered ascending, drag entries within the table and the orders will be re-saved.

## Pagination

As of version 2.1.4 Order entries has an additional pagination order per entry. Through this one can set a particular section to be paginated or not.

When using normal pagination the extension makes sure that entries within the page start with the offset of that particular page. 
For Example if you're on page 3 with 20 entries per page, the first entry will take a minimum index of 41. As this is the least possible to be after the last one of the previous page.

## Filtered Views

Since Filtering made it to the core, some problems have arisen in regards to Order Entries. In it's previous form filtering and re-ordering would reset the index of that particular sort to start from 1.
This is not always the expected result, with the changes implemented in this version, the offset will start from the **minimum** index found in the entry.
So if you're sorting entries which were numbered `4`, `5` and `6` they will keep the same index values when resorted.
However if you are sorting `4`, `6` and `12` sorting would give the following indices; `4`, `5` and `6`.

Note that if you plan to use the sorting values only within the filtered views, and use that within your data sources this change will not impact your workflow.

Expect some further changes in the near future in regards to filtered views and support of multiple sort values for different filters.