define [
	'jquery'
	'lodash'
	'backbone'
	'marionette'
	'hbs!templates/membres'
	'hbs!templates/membres/list'
],($, _, Backbone, Marionette, hbsTemplate, hbsListTemplate)->

	PAGINATION_LINK_COUNT = 10
	DEFAULT_PAGE_SIZE = 10

	class ListView extends Marionette.ItemView
		tagName: 'membre-list'
		template: hbsListTemplate

		serializeData: ->
			#console.log '>>>>>>>>>>',@options
			@options

	class MembresView extends Marionette.LayoutView
		className: 'membres'
		template: hbsTemplate

		regions:
			mainRegion: '.mainRegion'

		initialize: ->
			@update @options

		update: (options)=>
			#console.log '>>>>>',options
			{pageSize, pageIndex} = options

			$.ajax
				url: 'services/listeMembres.php'
				data: options
				success: @_handleListeMembres

			@collection = new Backbone.Collection


		_handleListeMembres: (data)=>
			#console.debug 'Received data:',data

			if not @$el?
				# if not rendered then retry when we are.
				@once 'render', =>
					@_handleListeMembres data
			else
				@mainRegion.show new ListView(@_addPaginationData data)

		_addPaginationData: (data)->
			pagination = {}

			firstIndex = Math.max 1, data.currentPage - PAGINATION_LINK_COUNT/2
			lastIndex = Math.min data.pageCount, firstIndex + PAGINATION_LINK_COUNT-1

			pagination.next = if lastIndex is data.pageCount
					firstIndex = Math.max 1, lastIndex - PAGINATION_LINK_COUNT+1

					disabled: true
				else
					index: data.currentPage+1

			pagination.prev = if firstIndex is 1
					disabled: true
				else
					index: data.currentPage-1

			pagination.items = for index in [firstIndex..lastIndex]
				index: index
				active: index is data.currentPage

			_.defaults {}, data,
				pagination: pagination
