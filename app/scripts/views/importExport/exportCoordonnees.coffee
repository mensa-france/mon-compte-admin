define [
	'marionette'
	'templates'
],(Marionette, templates)->

	class ExportCoordonneesView extends Marionette.ItemView
		className: 'panel panel-info export coordonnees'
		template: templates.importExport_exportCoordonnees
