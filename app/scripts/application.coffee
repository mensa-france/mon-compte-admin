define [
	'underscore'
	'marionette'
	'views/layout'
	'views/operations'
	'views/membres'
],(_, Marionette, LayoutView, OperationsView, MembresView)->

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

		if not pageSize or pageSize < 10
			router.navigate 'membres/10/1', trigger:true
		else if not pageIndex or pageIndex < 1
			router.navigate "membres/#{pageSize}/1", trigger:true unless pageIndex
		else
			layout.show 'membres', new MembresView
				pageSize: pageSize
				pageIndex: pageIndex

	app.redirectToDefault = ->
		router.navigate '', trigger:true # goes to default view.

	console.groupEnd()

	app #return the app instance.
