define [
	'marionette'
	'hbs!templates/importExport'
],(Marionette, hbsTemplate)->

	class ImportExportView extends Marionette.ItemView
		template: hbsTemplate

		onRender: ->
