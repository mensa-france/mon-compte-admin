define [
	'marionette'
	'templates'
],(Marionette, templates)->

	class ExportMembresCotisantsView extends Marionette.ItemView
		className: 'panel panel-info export cotisants'
		template: templates.importExport_exportMembresCotisants
