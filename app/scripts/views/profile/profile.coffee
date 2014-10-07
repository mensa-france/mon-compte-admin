define [
	'marionette'
	'hbs!templates/profile/profile'
],(Marionette, hbsTemplate)->

	class ProfileView extends Marionette.ItemView
		className: 'well profile'
		template: hbsTemplate

		serializeData: ->
			@options
