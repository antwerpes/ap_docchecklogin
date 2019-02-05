ap_docchecklogin
================

Integrate DocCheck Login with your TYPO3 6.x - 9.7.x projects.


## Manual
see the extension manual in the Documentation folder.

## License
Have a look at the extension manual and README.txt

## Preparing your development environment
When you add something to the documentation, you will need to install sphinx to preview the documentation. Go to http://sphinx-doc.org/latest/install.html. 
Then create a folder `\_not_versioned` inside the `Documentation` folder. Render the Docs using `sphinx-build Documentation Documentation/_not_versioned/out` from within the extension folder

## Releasing a version
* Increase the version number in ext_emconf.php
* add a new line on top of the ChangeLog file
* git commit and tag the version
* zip everything except .git, .gitignore, Documentation/\_not_versioned and existing zips
* name the zip file `ap_docchecklogin_<version>.zip`
    (e.g. ap_docchecklogin_1.0.7.zip)
