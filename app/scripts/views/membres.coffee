define [
	'jquery'
	'lodash'
	'backbone'
	'marionette'
	'templates'
],($, _, Backbone, Marionette, templates)->

	PAGINATION_LINK_COUNT = 10
	DEFAULT_PAGE_SIZE = 20

	class ListView extends Marionette.ItemView
		tagName: 'membre-list'
		template: templates.membres_list

		events:
			'dblclick .membre': 'handleMembreDblClick'

		serializeData: ->
			#console.log '>>>>>>>>>>',@options
			@options

		handleMembreDblClick: (event)->
			$target = $(event.currentTarget)
			@trigger 'select', $target.data('numeromembre')

	class MembresView extends Marionette.LayoutView
		className: 'membres'
		template: templates.membres

		@DEFAULT_PAGE_SIZE: DEFAULT_PAGE_SIZE # used to make value public.

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

		_handleListeMembres: (data)=>
			#console.debug 'Received data:',data

			if not @$el?
				# if not rendered then retry when we are.
				@once 'render', =>
					@_handleListeMembres data
			else
				view = new ListView @_addPaginationData(data)
				view.on 'select', (numeroMembre)=>
					@trigger 'select', numeroMembre

				@mainRegion.show view

		_addPaginationData: (data)->
			pagination = {}

			firstIndex = Math.max 1, data.currentPage - PAGINATION_LINK_COUNT/2
			lastIndex = Math.min data.pageCount, firstIndex + PAGINATION_LINK_COUNT-1

			if lastIndex is data.pageCount
				firstIndex = Math.max 1, lastIndex - PAGINATION_LINK_COUNT+1

			pagination.next = if data.currentPage >= data.pageCount
					disabled: true
				else
					index: data.currentPage+1

			pagination.prev = if data.currentPage is 1
					disabled: true
				else
					index: data.currentPage-1

			pagination.items = for index in [firstIndex..lastIndex]
				index: index
				active: index is data.currentPage

			_.defaults {}, data,
				pagination: pagination
