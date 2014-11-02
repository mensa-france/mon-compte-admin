define [
	'marionette'
	'templates'
	'spin'
],(Marionette, templates, Spin)->

	SPIN_OPTIONS =
		lines: 13
		length: 4
		width: 2
		radius: 3
		corners: 1
		rotate: 0
		direction: 1
		color: '#000'
		speed: 1
		trail: 60
		shadow: false
		hwaccel: false
		className: 'spinner'
		zIndex: 2e9
		top: '50%'
		left: '50%'

	class UpdateStatusView extends Marionette.ItemView
		className: 'panel panel-danger import updateStatus'
		template: templates.importExport_updateStatus

		ui:
			iframe: 'iframe'
			submitButton: 'button.import'
			messageZone: '.messageZone'
			waitZone: '.waitIndicator'

		events:
			'submit form': 'handleFormSubmit'
			'load iframe': 'handleImportFinished'

		onRender: ->
			@ui.iframe.load @handleImportFinished

		getIframeContent: =>
			@ui.iframe.contents().text()

		handleFormSubmit: (event)->
			@formSubmitted = true
			@ui.submitButton.attr 'disabled',true
			@clearMessage()

			@spinner = new Spin(SPIN_OPTIONS).spin(@ui.waitZone[0])

		handleImportFinished: =>
			if not @formSubmitted
				# then it's just the view loading.
				return

			@ui.submitButton.removeAttr 'disabled'
			@spinner?.stop()
			delete @spinner

			payload = JSON.parse @getIframeContent()

			if (payload.errors)
				@showError 'Erreurs pendant la mise à jour',payload.errors.join('\n')

			if (payload.message is 'Completed')
				# then everything is AWESOME !
				@showSuccess "Mise à jour terminée","La mise à jour s'est terminée avec succès!"

		clearMessage: ->
			console.debug 'Clearing existing messages.'
			@ui.messageZone.empty()

		showError: (title, message)=>
			@showMessage title,message,templates.messages_error

		showInfo: (title, message)=>
			@showMessage title,message,templates.messages_info

		showSuccess: (title, message)=>
			@showMessage title,message,templates.messages_success

		showMessage: (title, message, template)=>
			$message = $(template
				title: title
				message: message
			)

			$message.appendTo(@ui.messageZone)
