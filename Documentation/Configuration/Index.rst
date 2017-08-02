.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

Configuration
=============

Custom View Templates
---------------------
You might want to change the HTML Code that renders the iFrame.

To do so, copy the ``Resources/Private/Templates/DocCheckAuthentication/`` folder
to, for example, your ``fileadmin/templates/``-Folder and set the following in
your TYPO3 Setup.

::

	# tell ap_docchecklogin to use the templates stored in fileadmin/templates/DocCheckAuthentication
	plugin.tx_apdocchecklogin.view.templateRootPaths.0 = fileadmin/templates/

References
----------

Configuration options in the extension manager


.. only:: html

	.. contents::
		:local:
		:depth: 1

.. _basicconfig:

Basic Configuration
^^^^^^^^^^^^^^^^^^^

.. t3-field-list-table::
 :header-rows: 1

 - :Property,30:
		Property:

   :Data type,10:
		Data type:

   :Description,60:
		Description:


 - :Property:
		basic.dcParam

   :Data type:
		string

   :Description:
		**Expected value for the "dc" GET-parameter**

		The Extension will check :php:`$_GET['dc']` for this value after a successful
		DocCheck Login. Set it to an arbitrary string that can be used as a url
		parameter.
		*This option does no apply when the* :ref:`routing <routingconf>` *feature is enabled.*

 - :Property:
		basic.dummyUser

   :Data type:
		string

   :Description:
		**User name of the dummy user.**

		This user will be logged in with your TYPO3 website, whenever a DocCheck User
		logs in successfully. The dummy user must be stored in PID as determined in
		:ts:`basic.dummyUserPid`.
		*This option does no apply when the* :ref:`unique key <uniquekeyconf>` *feature is enabled.*

 - :Property:
		basic.dummyUserPid

   :Data type:
		string

   :Description:
		**Dummy User's PID**

		The extension will look for the dummy user or the configured user groups
		(when using the unique key feature) on the page (or storage folder) with
		this id.

 - :Property:
		basic.useFeLoginRedirect

   :Data type:
		boolean *(def.: false)*

   :Description:
		**Use Felogin's Redirect-Feature**

		When set to 1, you can determine a redirect-after-login-target in the
		dummy user's properties. If set, the user will be redirected to that
		target after login. Requires felogin, I think.

		The Login target page in DocCheck Cream has to be configured to a page
		which includes the DocCheck Login-Plugin for the redirect action to take place.

.. _uniquekeyconf:

UniqueKey Configuration
^^^^^^^^^^^^^^^^^^^^^^^

.. t3-field-list-table::
 :header-rows: 1

 - :Property,30:
		Property:

   :Data type,10:
		Data type:

   :Description,60:
		Description:

 - :Property:
		uniquekey.uniqueKeyEnable

   :Data type:
		boolean *(def.: false)*

   :Description:
		**Enable the DocCheck UniqueKey feature.**

		This requires you to set the according DocCheck Special in your
		CReaM Configuration. When enabled, the extension will generate
		one unique frontend user for each unique DocCheck Key.


 - :Property:
		uniquekey.uniqueKeyGroup

   :Data type:
		integer

   :Description:
		**FE-Group for Unique Users**

		The generated unique users will be added to the ID with this group.
		This group must be found in the page which you configured in
		:ts:`basic.dummyUserPid`

 - :Property:
		uniquekey.dcPersonalEnable

   :Data type:
		boolean *(def.: false)*

   :Description:
		**Enable DocCheck Personal Feature**

		When you enable DocCheck Personal Special in your DocCheck CreaM
		configuration and have this flag enabled, the extension will add
		some user specific data to the generated fe_user record – if
		the user agrees, of course.

.. _routingconf:

Routing Configuration
^^^^^^^^^^^^^^^^^^^^^

.. t3-field-list-table::
 :header-rows: 1

 - :Property,30:
		Property:

   :Data type,10:
		Data type:

   :Description,60:
		Description:


 - :Property:
		routing.routingEnable

   :Data type:
		boolean *(def.: false)*

   :Description:
		**Enable the DocCheck Routing feature.**

		This requires you to set some routes in your DocCheck CReaM
		Configuration. Each target must look exactly as described in
		:ref:`admin-step4`:

		``http://your-typo3-site.example.org/login/?logintype=login&dc=19865c8sak``

		**But: You set a different dcParam for each route.**
		Each dcParam will be routed to one frontend user group.

		**This requires the** :ref:`unique key <uniquekeyconf>` **feature.**

 - :Property:
		routing.routingMap

   :Data type:
		string

   :Description:
		**The Routing Map**

		This map resolves each dc-Param to one frontend user group.

		**Format:**

		``<GROUP_ID>=<DC_PARAM>,<GROUP_ID>=<DC_PARAM>...``

		**Example:**

		``2=1337,3=akDJKw82,5=dk8Dkkv``

		Now, when a user is routed to the URL with ?dc=1337, a frontend
		user will be created and added to the group #2. You get the drill.

.. _settingsconf:

Settings Configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. t3-field-list-table::
 :header-rows: 1

 - :Property,30:
		Property:

   :Data type,10:
		Data type:

   :Description,60:
		Description:


 - :Property:
		settings.loginOverrideId

   :Data type:
		string

   :Description:
		**Override Login ID.**

		This numeric parameter overrides the used Doccheck Login ID.
		Especially useful for working in multiple environments.

		**Example:**
		::

			[globalString = IENV:HTTP_HOST = stage.domain.com]
				plugin.tx_apdocchecklogin.settings.loginOverrideId = 1111111111
			[global]


.. _crawlerconf:

Crawler Configuration
^^^^^^^^^^^^^^^^^^^^^

.. t3-field-list-table::
 :header-rows: 1

 - :Property,30:
		Property:

   :Data type,10:
		Data type:

   :Description,60:
		Description:


 - :Property:
		crawling.crawlingEnable

   :Data type:
		boolean *(def.: false)*

   :Description:
		**Enable the DocCheck Crawler feature.**

		Enable the possibility to bypass the DocCheck Login for the DocCheck crawler.
		This requires you to set the correct DocCheck Crawler IP and an existing Website User in Typo3.

 - :Property:
		crawling.crawlingUser

   :Data type:
		string

   :Description:
		**The User that has access to protected urls.**

		Defaults to basic.dummyUser

 - :Property:
		crawling.crawlingIp

   :Data type:
		string

   :Description:
		**The IP address that has access to protected urls.**
