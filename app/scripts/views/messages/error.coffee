define [
	'marionette'
	'hbs!templates/messages/error'
],(Marionette, hbsTemplate)->

	class ErrorMessageView extends Marionette.ItemView
		template: hbsTemplate

		serializeData: ->
			@options
