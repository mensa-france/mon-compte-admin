define [
	'marionette'
	'hbs!templates/importExport'
	'views/importExport/exportCoordonnees'
	'views/importExport/importCotisations'
],(Marionette, hbsTemplate, ExportCoordonneesView, ImportCotisationsView)->

	class ImportExportView extends Marionette.Layout
		className: 'importExport'
		template: hbsTemplate

		regions:
			region1: '.region1'
			region2: '.region2'
			region3: '.region3'

		onRender: ->
			@region1.show new ExportCoordonneesView
			@region2.show new ImportCotisationsView
