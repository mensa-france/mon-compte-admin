define [
	'marionette'
	'hbs!templates/importExport/importMembres'
	'hbs!templates/messages/error'
	'hbs!templates/messages/info'
	'hbs!templates/messages/success'
	'spin'
],(Marionette, hbsTemplate, errorTemplate, infoTemplate, successTemplate, Spin)->

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

	class ImportMembresView extends Marionette.ItemView
		className: 'panel panel-danger import membres'
		template: hbsTemplate

		ui:
			iframe: 'iframe'
			submitButton: 'button.import'
			messageZone: '.messageZone'
			waitZone: '.waitIndicator'
			fileSelector: '.fileselector'

		events:
			'submit form': 'handleFormSubmit'
			'load iframe': 'handleImportFinished'

		onRender: ->
			@ui.iframe.load @handleImportFinished

		getIframeContent: =>
			@ui.iframe.contents().text()

		handleFormSubmit: (event)->
			if not @ui.fileSelector.val()? || @ui.fileSelector.val() is ''
				@showError 'Erreur', 'Vous devez sélectionner un fichier.'
				event.preventDefault()
				return false

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
				@showError 'Erreurs pendant l\'import',payload.errors.join('\n')

			if (payload.message is 'Completed')
				# then everything is AWESOME !
				@showSuccess "Import terminé","L'import s'est terminé avec succès!"

		clearMessage: ->
			console.debug 'Clearing existing messages.'
			@ui.messageZone.empty()

		showError: (title, message)=>
			@showMessage title,message,errorTemplate

		showInfo: (title, message)=>
			@showMessage title,message,infoTemplate

		showSuccess: (title, message)=>
			@showMessage title,message,successTemplate

		showMessage: (title, message, template)=>
			$message = $(template
				title: title
				message: message
			)

			$message.appendTo(@ui.messageZone)
