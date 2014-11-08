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
			@collection = new Backbone.Collection
			@collection.reset @options.cotisations

		serializeData: ->
			@options.profile

		handleDeleteCotisation: (view, cotisationId)->
			options =
				numero_membre: @options.profile.numero_membre
				cotisation_id: cotisationId

			$.ajax
				url: 'services/deleteCotisation.php'
				data: options
				success: @handleDeleteResult
				error: @handleDeleteResult

		handleDeleteResult: =>
			@trigger 'refresh'
