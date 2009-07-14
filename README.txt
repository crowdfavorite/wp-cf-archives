## CF Archives

- The CF Archives plugin gives the blog owner the ability to display archives in a much less server intensive way than the current WordPress implementation.  This plugin updates the database each time a post is saved and deleted to display the latest archived data.  The archives plugin uses "Template Tags" or PHP functions to display content on pages.  If an archive is desired on a page it will need to be added through the theme code.

### Settings

- To find the settings page, navigate to the WP Admin, then click on the "CF Archives" link under the "Settings" area of the left sidebar
- The following are global options.  These options can be overridden using the template tags that are added to the currently active theme
	1.  Show Post Preview
		- This option gives the user the ability to see the excerpt from the post
	2.  Show Year Header
		- This option will display the year in the listing of posts.  This is not needed for the year link in the year/month list at the top of the archives to work
	3.  Display year/month hide links
		- This option gives the user the ability to hide/show the months for the selected year.  Note: the "Show Year Header" option must be enabled for this to work
- Once settings have been changed, the "Save Settings" button must be clicked to save

### Hiding Years

- The archives plugin gives the admin the ability to hide years, globally, from the archives.
- When a year is checked it will not be displayed in the archive list.
- This option can be overridden using the template tag in the currently active theme
- Once displayed years have been changed, the "Save Settings" button must be clicked to save

### Hiding Years/Month Per Category

- To add a category to have years/months removed:
	1.  Click the "Add New Category" button in the Remove Years/Months by Category section
	2.  Select the category from the drop down
	3.  Click the checkbox to the left of the year/month to be removed
		- If a year is clicked, all months for that year will be disabled and automatically removed
	4.  Click the "Save Settings" button to save changes
- To remove a category from the list:
	1.  Click the "Remove Category" button, then click "OK" from the confirmation box
	2.  Click the "Save Settings" button to save changes

### Rebuilding the Archives

- From time to time the archives can become corrupted or not display proper data due to various reasons
- When this happens, navigate to the settings screen and click the "Rebuild Archives" button
- When the rebuild button has been clicked it is essential that the browser window stay open and on the current page until the script is complete
- When the script is complete the area below the button will display "Archive procesing complete."
