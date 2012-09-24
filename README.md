These are sample files used in the project of data and presentation migration from MySQl + Smarty templates into CodeIgniter + Quinstreet's proprietary CMS Content Engine.

<h2>scrape</h2>

I have included the base class Scrape and one of the classes that extends it, ScrapeCore, which mined the data for the sections artigos, noticias and dicas. It builds an XML document as specified by Quinstreet's proprietary CMS Content Engine's documentation and was used to transfer the data from the original MySQL database into Content Engine.

<h2>smarty</h2>

The folder smarty contains code sample for the original artigos section.

<h2>CI</h2>

The folder CI contains the controller, model and view for the artigos section after the migration into CodeIgniter