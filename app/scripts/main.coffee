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

		'bootstrap.affix': '../bower_components/bootstrap-sass-official/vendor/assets/javascripts/bootstrap/affix'
		'bootstrap.alert': '../bower_components/bootstrap-sass-official/vendor/assets/javascripts/bootstrap/alert'
		'bootstrap.button': '../bower_components/bootstrap-sass-official/vendor/assets/javascripts/bootstrap/button'
		'bootstrap.carousel': '../bower_components/bootstrap-sass-official/vendor/assets/javascripts/bootstrap/carousel'
		'bootstrap.collapse': '../bower_components/bootstrap-sass-official/vendor/assets/javascripts/bootstrap/collapse'
		'bootstrap.dropdown': '../bower_components/bootstrap-sass-official/vendor/assets/javascripts/bootstrap/dropdown'
		'bootstrap.tab': '../bower_components/bootstrap-sass-official/vendor/assets/javascripts/bootstrap/tab'
		'bootstrap.transition': '../bower_components/bootstrap-sass-official/vendor/assets/javascripts/bootstrap/transition'
		'bootstrap.scrollspy': '../bower_components/bootstrap-sass-official/vendor/assets/javascripts/bootstrap/scrollspy'
		'bootstrap.modal': '../bower_components/bootstrap-sass-official/vendor/assets/javascripts/bootstrap/modal'
		'bootstrap.tooltip': '../bower_components/bootstrap-sass-official/vendor/assets/javascripts/bootstrap/tooltip'
		'bootstrap.popover': '../bower_components/bootstrap-sass-official/vendor/assets/javascripts/bootstrap/popover'

	map:
		'bootstrap.*':
			'jQuery': 'jquery'

	shim:
		'bootstrap.affix':
			deps: ['jquery']
		'bootstrap.alert':
			deps: ['jquery']
		'bootstrap.button':
			deps: ['jquery']
		'bootstrap.carousel':
			deps: ['jquery']
		'bootstrap.collapse':
			deps: ['jquery']
		'bootstrap.dropdown':
			deps: ['jquery']
		'bootstrap.tab':
			deps: ['jquery']
		'bootstrap.transition':
			deps: ['jquery']
		'bootstrap.scrollspy':
			deps: ['jquery']
		'bootstrap.modal':
			deps: ['jquery']
		'bootstrap.tooltip':
			deps: ['jquery']
		'bootstrap.popover':
			deps: ['jquery','bootstrap.tooltip']

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

