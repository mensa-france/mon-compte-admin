define [
	'backbone'
	'marionette'
	'templates'
],(Backbone, Marionette, templates)->

	class CotisationView extends Marionette.ItemView
		tagName: 'tr'
		template: templates.profile_cotisation

		events:
			'click .deleteCotisation': 'handleDeleteCotisationClick'

		handleDeleteCotisationClick: ->
			cotisationId = @model.get 'id'
			if confirm "Vous allez supprimer la cotisation #{cotisationId}\nVoulez-vous continuer ?"
				@trigger 'delete', cotisationId

	class ProfileView extends Marionette.CompositeView
		className: 'well profile'
		template: templates.profile_profile

		childView: CotisationView
		childViewContainer: '.cotisationList'

		childEvents:
			'delete': 'handleDeleteCotisation'

		initialize: ->
			@model = new Backbone.Model @options.profile
			@collection = new Backbone.Collection @options.cotisations

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
