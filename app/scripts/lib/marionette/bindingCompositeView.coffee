define [
	'marionette'
	'lib/marionette/binding'
], (Marionette, Binding)->

	class BindingCompositeView extends Marionette.CompositeView
		bindingDataAttribute: 'binding'

		constructor: ->
			super
			Binding.enable @
