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
* Everytime you create a new release, a webhook will publish this automatically to the TYPO3 TER.
