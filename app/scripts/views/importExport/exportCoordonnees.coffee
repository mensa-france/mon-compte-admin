define [
	'marionette'
	'hbs!templates/importExport/exportCoordonnees'
],(Marionette, hbsTemplate)->

	class ExportCoordonneesView extends Marionette.ItemView
		className: 'panel panel-info export coordonnees'
		template: hbsTemplate
