define [
	'marionette'
	'hbs!templates/operations'
	'views/importExport/exportCoordonnees'
	'views/importExport/importCotisations'
	'views/importExport/importMembres'
	'views/importExport/updateStatus'
	'views/importExport/updateLdap'
],(Marionette, hbsTemplate, ExportCoordonneesView, ImportCotisationsView, ImportMembresView, UpdateStatusView, UpdateLdapView)->

	class OperationsView extends Marionette.LayoutView
		className: 'operations'
		template: hbsTemplate

		regions:
			region1: '.region1'
			region2: '.region2'
			region3: '.region3'
			region4: '.region4'
			region5: '.region5'

		onRender: ->
			@region1.show new ExportCoordonneesView
			@region2.show new ImportMembresView
			@region3.show new ImportCotisationsView
			@region4.show new UpdateStatusView
			@region5.show new UpdateLdapView
