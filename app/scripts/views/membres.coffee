define [
	'marionette'
	'hbs!templates/membres'
],(Marionette, hbsTemplate)->

	class MembresView extends Marionette.ItemView
		className: 'membres'
		template: hbsTemplate
