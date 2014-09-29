define [
	'marionette'
	'hbs!templates/layout'
	'views/operations'
],(Marionette, hbsTemplate, OperationsView)->

	class LayoutView extends Marionette.Layout
		template: hbsTemplate

		regions:
			mainRegion: '#mainRegion'

		ui:
			navitems: 'ul.navbar-nav li'

		onRender: ->
			@mainRegion.show new OperationsView
