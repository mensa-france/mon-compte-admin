define [
	'backbone'
	'marionette'
	'lib/marionette/bindingCompositeView'
	'templates'
	'templates/helpers/formatDate'
	'bootstrap-datepicker'
	'bootstrap-notify'
],(Backbone, Marionette, BindingCompositeView, templates, formatDateHelper)->

	TITLE_MAPPING =
		mister: 'M.'
		mrs: 'Mme'
		ms: 'Mlle'

	class CotisationView extends Marionette.ItemView
		tagName: 'tr'
		template: templates.profile_cotisation

		events:
			'click .deleteCotisation': 'handleDeleteCotisationClick'

		handleDeleteCotisationClick: ->
			cotisationId = @model.get 'id'
			if confirm "Vous allez supprimer la cotisation #{cotisationId}\nVoulez-vous continuer ?"
				@trigger 'delete', cotisationId

	class ProfileView extends BindingCompositeView
		className: 'well profile'
		template: templates.profile_profile

		childView: CotisationView
		childViewContainer: '.cotisationList'

		events:
			'submit #profileForm': 'handleProfileFormSubmit'
			'click .cancelButton': 'refreshData' # Reload from server.
			'click .submitButton': 'handleProfileFormSubmit'

		childEvents:
			'delete': 'handleDeleteCotisation'

		ui:
			dateInputGroups: '.input-group.date'
			notificationArea: '.notifications'

		bindingOptions:
			date_inscription:
				onGet: 'toDisplayDate'
				onSet: 'toInternalDate'
				initialize: 'updateDatePickers'
				afterUpdate: 'updateDatePickers'
			date_naissance:
				onGet: 'toDisplayDate'
				onSet: 'toInternalDate'
				initialize: 'updateDatePickers'
				afterUpdate: 'updateDatePickers'
			date_expiration:
				onGet: 'toDisplayDate'
			civilite:
				onGet: 'toDisplayTitle'


		updateDatePickers: ->
			@ui.dateInputGroups.datepicker 'update' # we need to refresh the values in date pickers.

		toDisplayTitle: (source)->
			TITLE_MAPPING[source] ? ''

		toDisplayDate: (source)->
			formatDateHelper(source)

		toInternalDate: (source)->
			source.replace /(\d{2})\/(\d{2})\/(\d{4})/, '$3-$2-$1'

		initialize: ->
			@model = new Backbone.Model @options.profile
			@collection = new Backbone.Collection @options.cotisations

		onRender: ->
			@ui.dateInputGroups.datepicker
				format: 'dd/mm/yyyy'
				language: 'fr'

		handleDeleteCotisation: (view, cotisationId)->
			options =
				numero_membre: @options.profile.numero_membre
				cotisation_id: cotisationId

			$.ajax
				url: 'services/deleteCotisation.php'
				data: options
				success: =>
					@showSuccess 'La cotisation a été supprimée.'
					@refreshDataKeepView()
				error: =>
					@showError 'Une erreur est survenue.'

		setData: (data)->
			@model.set data.profile
			@collection.reset data.cotisations

		_refreshData: (keepView)=>
			@trigger 'refresh', keepView

		refreshData: =>
			@_refreshData()

		refreshDataKeepView: =>
			@_refreshData true

		refreshCotisations: =>
			$.ajax
				url: 'services/listeCotisations.php'
				data:
					numero_membre: @options.profile.numero_membre
				success: (data)=>
					@collection.reset data.cotisations

		handleProfileFormSubmit: (event)->
			event?.preventDefault()
			$.ajax
				url: 'services/updateMembreProfile.php'
				data: @model.toJSON()
				success: @handleProfileSave
				error: @refreshData

		handleProfileSave: (data)=>
			if not _.isEmpty data?.errors
				@showError data.errors.join '<br>'
			else
				@showSuccess 'Profile enregistré avec succès.'
				@refreshDataKeepView()

		showSuccess: (message)->
			@ui.notificationArea.notify(message:message).show()

		showError: (message)->
			@ui.notificationArea.notify(message:message, type:'danger').show()
