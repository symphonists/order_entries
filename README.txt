Order Entries field
------------------------------------

Version: 1.5
Author: Nick Dunn (nick.dunn@airlock.com)
Build Date: 2009-05-12
Requirements: Symphony integration version (2.0.3 WIP)

[INSTALLATION]

1. Upload the 'order_entries' folder in this archive to your Symphony 'extensions' folder.

2. Enable it by selecting the "Order Entries", choose Enable from the with-selected menu, then click Apply.

3. You can now add the "Entry Order" field to your sections.

[USAGE]

1. Add the "Entry Order" field to your section and tick the "Show column" box.

2. When viewing the section under the Publish menu, order the table by the Entry Order field.

3. When ordered ascending, drag entries within the table and the orders will be re-saved.

[UPDATING]
If you are upgrading from a previous version you will need to make a database change. This assumes you are using the default 'sym_' table prefix.

	ALTER TABLE `sym_fields_order_entries` ADD `force_sort` enum('yes','no') default 'no';
	
You will then need to re-save any Section with an Entry Order field included.