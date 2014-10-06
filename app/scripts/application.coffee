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
				'*path': 'redirectToDefault'

		Backbone.history.start()

	app.showOperations = ->
		layout.show 'operations', new OperationsView

	app.showMemberList = ->
		layout.show 'membres', new MembresView

	app.redirectToDefault = ->
		router.navigate '', trigger:true # goes to default view.

	console.groupEnd()

	app #return the app instance.
