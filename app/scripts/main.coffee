require.config
	paths:
		jquery: '../bower_components/jquery/dist/jquery'

		underscore: '../bower_components/underscore/underscore'

		backbone: '../bower_components/backbone/backbone'
		marionette: '../bower_components/backbone.marionette/lib/backbone.marionette'

		handlebars: '../bower_components/handlebars/handlebars.runtime'

		lodash: '../bower_components/lodash/dist/lodash'

		consolePolyfill: '../bower_components/console-polyfill/index'

		spin: '../bower_components/spinjs/spin'

		bootstrap: '../bower_components/bootstrap-sass-official/assets/javascripts/bootstrap'

	shim:
		bootstrap:
			deps: [
				'jquery'
			]

	packages: [
		'templates/helpers'
	]

require [
	'consolePolyfill'
	'application'
	'version'
	'bootstrap'
], (consolePolyfill, app, Version)->
	console.log 'Application version:',Version

	app.setMainRegion '#container'
	app.start()

