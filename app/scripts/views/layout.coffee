define [
	'jquery'
	'marionette'
	'hbs!templates/layout'
	'views/operations'
	'views/membres'
],($, Marionette, hbsTemplate, OperationsView, MembresView)->

	VIEW_MAPPING =
		'#': OperationsView
		'#membres': MembresView

	ACTIVE_CLASS = 'active'

	class LayoutView extends Marionette.Layout
		template: hbsTemplate

		regions:
			mainRegion: '#mainRegion'

		ui:
			navItems: 'ul.navbar-nav li'
			navItemLinks: 'ul.navbar-nav li a'

		initialize: ->
			window.addEventListener "hashchange", @_handleHash, false

		_handleHash: =>
			hash = location.hash
			hash = '#' if hash is ''

			viewType = VIEW_MAPPING[hash] ?= VIEW_MAPPING['#']

			@ui.navItems.removeClass ACTIVE_CLASS

			@mainRegion.show new viewType

			@ui.navItemLinks.each (index, item)->
				$item = $(item)
				if $item.attr('href') is hash
					$item.parent().addClass ACTIVE_CLASS

		onRender: ->
			@_handleHash()
