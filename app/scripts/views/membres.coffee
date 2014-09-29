define [
	'jquery'
	'backbone'
	'marionette'
	'hbs!templates/membres'
	'hbs!templates/membres/list'
],($, Backbone, Marionette, hbsTemplate, hbsListTemplate)->

	class ListView extends Marionette.ItemView
		tagName: 'membre-list'
		template: hbsListTemplate

		serializeData: ->
			@options

	class MembresView extends Marionette.LayoutView
		className: 'membres'
		template: hbsTemplate

		regions:
			mainRegion: '.mainRegion'

		initialize: ->
			$.ajax
				url: 'services/listeMembres.php'
				success: @_handleListeMembres

			@collection = new Backbone.Collection

		_handleListeMembres: (data)=>
			if not @$el?
				# if not rendered then retry when we are.
				@once 'render', =>
					@_handleListeMembres data
			else
				@mainRegion.show new ListView
					membres: data
