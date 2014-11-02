define [
	'marionette'
	'templates'
],(Marionette, templates)->

	class ProfileView extends Marionette.ItemView
		className: 'well profile'
		template: templates.profile_profile

		serializeData: ->
			@options
