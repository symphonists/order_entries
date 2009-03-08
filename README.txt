
Order Entries field
------------------------------------

Version: 1.2
Author: Nick Dunn (nick.dunn@airlock.com)
Build Date: 2009-03-08
Requirements: Symphony 2.0.1

	
[INSTALLATION]

1. Upload the 'order_entries' folder in this archive to your Symphony 'extensions' folder.

2. Enable it by selecting the "Order Entries", choose Enable from the with-selected menu, then click Apply.

3. You can now add the "Entry Order" field to your sections.

[USAGE]

1. Add the "Entry Order" field to your section and tick the "Show column" box.

2. When viewing the section under the Publish menu, order the table by the Entry Order field.

3. When ordered ascending, drag entries within the table and the orders will be resaved.

[CHANGES]

1.2
- Save page now uses native Symphony content pages (improves compatibility with future Symphony releases)
- Improved the way path to the save page is evaluated

1.1
- Table prefixes no longer hard coded as 'sym_'
- Check for Publish index page uses Symphony methods rather than splitting the querystring

1.0
- Fixed bug with changing login methods in 2.0.1 (thanks go to michael-s)

0.3
- Fixed issue where AJAX call URL was incorrect when Symphony is installed in a subdirectory

0.2
- Support for multiple pages (removes paging entirely)
- Added label to page heading to alert user to drag functionality
- Added login cookie security to AJAX save page

0.1
- Initial release