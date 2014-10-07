define [
	'underscore'
	'marionette'
	'views/layout'
	'views/operations'
	'views/membres'
	'views/profile'
],(_, Marionette, LayoutView, OperationsView, MembresView, ProfileLayoutView)->

	app = new Marionette.Application

	console.group 'Initializing application.'

	layout = new LayoutView

	router = null

	app.setMainRegion = (selector)->
		app.addRegions
			container:
				selector: selector

	app.addInitializer ->
		app.container.show layout

		router = new Marionette.AppRouter
			controller: app

			appRoutes:
				'': 'showOperations'
				'profile': 'showProfile'
				'profile/:memberId': 'showProfile'
				'membres': 'showMemberList'
				'membres/:pagesize': 'showMemberList'
				'membres/:pagesize/:pageIndex': 'showMemberList'
				'*path': 'redirectToDefault'

		Backbone.history.start()

	app.showOperations = ->
		layout.show 'operations', new OperationsView

	app.showMemberList = (pageSize, pageIndex)->
		pageSize = parseInt pageSize
		pageIndex = parseInt pageIndex

		if not pageSize or pageSize < MembresView.DEFAULT_PAGE_SIZE
			router.navigate "membres/#{MembresView.DEFAULT_PAGE_SIZE}/1", trigger:true
		else if not pageIndex or pageIndex < 1
			router.navigate "membres/#{pageSize}/1", trigger:true unless pageIndex
		else
			currentView = layout.getCurrentView()

			if currentView instanceof MembresView
				currentView.update
					pageSize: pageSize
					pageIndex: pageIndex
			else
				layout.show 'membres', new MembresView
					pageSize: pageSize
					pageIndex: pageIndex

	app.showProfile = (memberId)->
		currentView = layout.getCurrentView()

		if currentView instanceof ProfileLayoutView
			currentView.update memberId
		else
			view = new ProfileLayoutView
				memberId: memberId

			view.on 'navigate', (memberId)->
				router.navigate "profile/#{memberId}"

			layout.show 'profile', view

	app.redirectToDefault = ->
		router.navigate '', trigger:true # goes to default view.

	console.groupEnd()

	app #return the app instance.
