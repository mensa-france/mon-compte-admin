define [
	'jquery'
	'marionette'
	'templates'
],($, Marionette, templates)->

	ACTIVE_CLASS = 'active'
	NAVID_ATTRIBUTE = 'navid'

	class LayoutView extends Marionette.LayoutView
		template: templates.layout

		regions:
			mainRegion: '#mainRegion'

		ui:
			navItems: 'ul.navbar-nav li'

		show: (navId, view)->
			if not @$el?
				@once 'render', =>
					@show navId, view
			else
				@mainRegion.show view
				@currentView = view

				@ui.navItems.each (index, item)->
					$item = $(item)
					if $item.data(NAVID_ATTRIBUTE) is navId
						$item.addClass ACTIVE_CLASS
					else
						$item.removeClass ACTIVE_CLASS

		getCurrentView: ->
			@currentView
