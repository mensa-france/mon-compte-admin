require.config
	paths:
		jquery: '../bower_components/jquery/dist/jquery'

		underscore: '../bower_components/underscore/underscore'

		backbone: '../bower_components/backbone/backbone'
		marionette: '../bower_components/backbone.marionette/lib/backbone.marionette'
		stickit: '../bower_components/backbone.stickit/backbone.stickit'

		handlebars: '../bower_components/handlebars/handlebars.runtime'

		lodash: '../bower_components/lodash/dist/lodash'

		consolePolyfill: '../bower_components/console-polyfill/index'

		spin: '../bower_components/spinjs/spin'

		bootstrap: '../bower_components/bootstrap-sass-official/assets/javascripts/bootstrap'
		'bootstrap-datepicker': '../bower_components/bootstrap-datepicker/js/bootstrap-datepicker'
		'bootstrap-datepicker.fr': '../bower_components/bootstrap-datepicker/js/locales/bootstrap-datepicker.fr'

	shim:
		bootstrap:
			deps: [
				'jquery'
			]

		'bootstrap-datepicker':
			deps: [
				'jquery'
				'bootstrap'
			]

		'bootstrap-datepicker.fr':
			deps: [
				'jquery'
				'bootstrap'
				'bootstrap-datepicker'
			]

	packages: [
		'templates/helpers'
	]

require [
	'consolePolyfill'
	'application'
	'bootstrap'
	'bootstrap-datepicker.fr'
], (consolePolyfill, app)->
	app.setMainRegion '#container'
	app.start()

