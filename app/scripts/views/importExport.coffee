define [
	'marionette'
	'hbs!templates/importExport'
],(Marionette, hbsTemplate)->

	class ImportExportView extends Marionette.ItemView
		className: 'importExport'
		template: hbsTemplate
