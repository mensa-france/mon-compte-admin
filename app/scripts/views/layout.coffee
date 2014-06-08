define [
	'marionette'
	'hbs!templates/layout'
	'views/importExport'
],(Marionette, hbsTemplate, ImportExportView)->

	class LayoutView extends Marionette.Layout
		template: hbsTemplate

		regions:
			mainRegion: '#mainRegion'

		onRender: ->
			@mainRegion.show new ImportExportView
