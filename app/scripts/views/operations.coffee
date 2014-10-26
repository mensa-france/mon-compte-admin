define [
	'marionette'
	'hbs!templates/operations'
	'views/importExport/exportCoordonnees'
	'views/importExport/exportMembresCotisants'
	'views/importExport/importCotisations'
	'views/importExport/importMembres'
	'views/importExport/updateStatus'
	'views/importExport/updateLdap'
],(Marionette, hbsTemplate, ExportCoordonneesView, ExportMembresCotisantsView, ImportCotisationsView, ImportMembresView, UpdateStatusView, UpdateLdapView)->

	class OperationsView extends Marionette.LayoutView
		className: 'operations'
		template: hbsTemplate

		regions:
			region1: '.region1'
			region2: '.region2'
			region3: '.region3'
			region4: '.region4'
			region5: '.region5'
			region6: '.region6'

		onRender: ->
			@region1.show new ExportCoordonneesView
			@region2.show new ExportMembresCotisantsView
			@region3.show new ImportMembresView
			@region4.show new ImportCotisationsView
			@region5.show new UpdateStatusView
			@region6.show new UpdateLdapView
