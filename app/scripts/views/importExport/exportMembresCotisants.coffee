define [
	'marionette'
	'hbs!templates/importExport/exportMembresCotisants'
],(Marionette, hbsTemplate)->

	class ExportMembresCotisantsView extends Marionette.ItemView
		className: 'panel panel-info export cotisants'
		template: hbsTemplate
