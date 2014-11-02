define [
	'marionette'
	'templates'
],(Marionette, templates)->

	class ErrorMessageView extends Marionette.ItemView
		template: templates.messages_error

		serializeData: ->
			@options
