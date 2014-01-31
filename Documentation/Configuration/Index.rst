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
	plugin.tx_apdocchecklogin.view.templateRootPath = fileadmin/templates/

References
----------

Configuration options in the extension manager


.. only:: html

	.. contents::
		:local:
		:depth: 1


Basic Configuration
^^^^^^^^^^^^^^^^^^^^

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
         Property:

   :Data type:
         Data type:

   :Description:
         Description:

   :Default:
         Default:


 - :Property:
         basic.dcParam

   :Data type:
         string

   :Description:
         The Extension will check :php:`$_GET['dc']` for this value after a successful
		 DocCheck Login. Set it to an arbitrary string that can be used as a url
		 parameter. 
		 *This option does **not** apply when the routing feature is enabled.*

   :Default:
         
 - :Property:
         basic.dummyUser

   :Data type:
         string

   :Description:
         User name of the dummy user. This user will be logged in with your TYPO3 website,
		 whenever a DocCheck User logs in successfully. The dummy user must be stored
		 in PID as determined in :ts:`basic.dummyUserPid`.
		 *This option does **not** apply when the unique key feature is enabled.*

   :Default:
   
 - :Property:
         basic.dummyUserPid

   :Data type:
         string

   :Description:
         The extension will look for the dummy user or the configured user groups
		 (when using the unique key feature) on the page (or storage folder) with
		 this id.

   :Default:
         
 - :Property:
         basic.useFeLoginRedirect

   :Data type:
         boolean

   :Description:
         When set to 1, you can determine a redirect-after-login-target in the
		 dummy user's properties. If set, the user will be redirected to that
		 target after login. Requires fe_user, I think.

   :Default:
         false


UniqueKey Configuration
^^^^^^^^^^^^^^^^^^^^

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
         Property:

   :Data type:
         Data type:

   :Description:
         Description:

   :Default:
         Default:


 - :Property:
         uniquekey.uniqueKeyEnable

   :Data type:
         boolean

   :Description:
         **Enables the DocCheck UniqueKey feature.** This requires you to set
		 the according DocCheck Special in your CReaM Configuration. When
		 enabled, the extension will generate one unique frontend user for
		 each unique DocCheck Key.

   :Default:
		 false

 - :Property:
         uniquekey.uniqueKeyGroup

   :Data type:
         integer

   :Description:
         The generated unique users will be added to the ID with this group.
		 This group must be found in the page which you configured in
		 :ts:`basic.dummyUserPid`

   :Default:
		 
 - :Property:
         uniquekey.dcPersonalEnable

   :Data type:
         boolean

   :Description:
         When you enable DocCheck Personal Special in your DocCheck CreaM
		 configuration and have this flag enabled, the extension will add
		 some user specific data to the generated fe_user record – if 
		 the user agrees, of course.

   :Default:
		 false
		 
		 
Routing Configuration
^^^^^^^^^^^^^^^^^^^^

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
         Property:

   :Data type:
         Data type:

   :Description:
         Description:

   :Default:
         Default:


 - :Property:
         routing.routingEnable

   :Data type:
         boolean

   :Description:
         **Enables the DocCheck Routing feature.** This requires you to set
		 some routes in your DocCheck CReaM Configuration. Each target must
		 look exactly as described in :ref:`admin-step4`:

		 ``http://your-typo3-site.example.org/login/?logintype=login&dc=19865c8sak``
		
		**But: You set a different dcParam for each route.**
		
		Each dcParam will be routed to one frontend user group.
		**This requires the unique key feature.**

   :Default:
		 false

 - :Property:
         routing.routingMap

   :Data type:
         string

   :Description:
         This map resolves each dc-Param to one frontend user group.
		 
		 Format:
		 ``<GROUP_ID>=<DC_PARAM>,<GROUP_ID>=<DC_PARAM>...``

		 Example: 
		 ``2=1337,3=akDJKw82,5=dk8Dkkv``
		 Now, when a user is routed to the URL with ?dc=1337, a frontend
		 user will be created and added to the group #2. You get the drill.

   :Default:
