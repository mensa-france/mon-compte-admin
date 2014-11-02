define [
	'jquery'
	'lodash'
	'marionette'
	'templates'
	'views/messages/error'
	'views/profile/profile'
],($, _, Marionette, templates, ErrorMessageView, ProfileView)->

	PROFILE_SERVICE_ADDRESS = 'services/getMembreProfile.php'

	class ProfileLayoutView extends Marionette.LayoutView
		className: 'profileLayout'
		template: templates.profile

		regions:
			resultRegion: '#resultRegion'

		ui:
			idInput: '#memberIdInput'

		events:
			'submit #memberIdForm': 'handleFormSubmit'

		serializeData: ->
			@options

		initialize: ->
			@update @options.memberId

		update: (memberId)=>
			if not @_isRendered
				@once 'render', =>
					@_fetchProfile memberId
			else
				@_fetchProfile memberId

		_fetchProfile: (memberId)=>
			@ui.idInput.val memberId
			@resultRegion.empty()

			if memberId
				$.ajax
					url: PROFILE_SERVICE_ADDRESS
					data:
						numero_membre: memberId
					success: @_processProfile
					error: @_processError

		_processProfile: (data)=>
			if not _.isEmpty data.errors
				for error in data.errors
					@resultRegion.show new ErrorMessageView(title:'Error fetching profile' ,message: error)
			else
				@resultRegion.show new ProfileView(data)

		_processError: =>
			console.error 'Error:',arguments

		getIdValue: ->
			@ui.idInput.val()

		handleFormSubmit: (event)->
			event.preventDefault()
			@trigger 'navigate',@getIdValue()
			@update @getIdValue()
