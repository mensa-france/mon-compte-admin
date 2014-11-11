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

		update: (memberId, keepView)=>
			if not @_isRendered
				@once 'render', =>
					@_fetchProfile memberId, keepView
			else
				@_fetchProfile memberId, keepView

		_fetchProfile: (memberId, keepView)=>
			@ui.idInput.val memberId
			@_clearProfileView() unless keepView

			if memberId
				$.ajax
					url: PROFILE_SERVICE_ADDRESS
					data:
						numero_membre: memberId
					success: (data)=>
						@_processProfile data, memberId
					error: @_processError

		_clearProfileView: =>
			@resultRegion.empty()
			delete @profileView

		_processProfile: (data, memberId)=>
			if not _.isEmpty data.errors
				@_clearProfileView()
				for error in data.errors
					@resultRegion.show new ErrorMessageView(title:'Error fetching profile' ,message: error)
			else
				if @profileView
					@profileView.setData data
				else
					@profileView = new ProfileView data
					@profileView.on 'refresh', (keepView)=>
						@update memberId, keepView

					@resultRegion.show @profileView

		_processError: =>
			console.error 'Error:',arguments

		getIdValue: ->
			@ui.idInput.val()

		handleFormSubmit: (event)->
			event.preventDefault()
			@trigger 'navigate',@getIdValue()
			@update @getIdValue()
