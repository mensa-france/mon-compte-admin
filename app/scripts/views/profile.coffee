define [
	'jquery'
	'lodash'
	'marionette'
	'hbs!templates/profile'
	'views/messages/error'
	'views/profile/profile'
],($, _, Marionette, hbsTemplate, ErrorMessageView, ProfileView)->

	PROFILE_SERVICE_ADDRESS = 'services/getMembreProfile.php'

	class ProfileLayoutView extends Marionette.LayoutView
		className: 'profileLayout'
		template: hbsTemplate

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

		handleFormSubmit: ->
			@trigger 'navigate',@getIdValue()
			@update @getIdValue()