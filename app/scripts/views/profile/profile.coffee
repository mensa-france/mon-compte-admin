define [
	'backbone'
	'marionette'
	'lib/marionette/bindingCompositeView'
	'templates'
	'templates/helpers/formatDate'
	'bootstrap-datepicker'
],(Backbone, Marionette, BindingCompositeView, templates, formatDateHelper)->

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

		childEvents:
			'delete': 'handleDeleteCotisation'

		ui:
			dateInputGroups: '.input-group.date'

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

		updateDatePickers: ->
			@ui.dateInputGroups.datepicker 'update' # we need to refresh the values in date pickers.

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
				success: @refreshCotisations
				error: @refreshCotisations

		refreshCotisations: =>
			$.ajax
				url: 'services/listeCotisations.php'
				data:
					numero_membre: @options.profile.numero_membre
				success: (data)=>
					@collection.reset data.cotisations
