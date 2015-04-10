#File Indexing for TYPO3 CMS 4.x

This Add-On provides file indexing for TYPO3 CMS 4.5, 4.6, and 4.7.

Files are being indexed during regular page indexing. When a page is being indexed using the Index Queue, solrfile hooks into the page rendering process. By doing so and by using file detectors the extension examins the content elements being rendered. When a file detector finds a file it is added to a separate File Index Queue. That File Index Queue is then processed in parallel and page indexing can continue without waiting for file indexing to finish.

To find files on pages and in content elements the extension comes with a set of file detectors. To detect files linked in records like news we use file attachment detectors. Custom file detectors and attachment detectors can be developed and added for custom download extensions and records not supported out-of-the-box.

The extension comes with file detectors for the following extensions:

    Core / fileadmin (text, text+image, table, and downloads content element)
    EXT:dam (text and text+image content elements)
    EXT:css_filelinks (text, text+image, table, and downloads content element)
    EXT:dam_filelinks (downloads content element)
    EXT:templavoila (Templavoila data structures)

The extension comes with file attachment detectors to detect files in a set of TCA fields:

    Core / fileadmin (file and text fields)
    EXT:dam (file and text fields)

We thank our Sponsors for enabling us to develop this Extension.
As this is a TYPO3 CMS 4.5 Extension we will not further continue the development.
Commercial Support can be purchased from us. 
