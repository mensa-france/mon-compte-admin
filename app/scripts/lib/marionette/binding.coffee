define [
	'lodash'
	'stickit'
], ()->

	enable: (view)->
		view.on 'render', =>
			if view.model?
				view.bindings = _.clone(view.bindings ? {})

				for key, value of view.model.toJSON()
					def =
						observe: key

					if view.bindingOptions? && view.bindingOptions[key]?
						def = _.defaults def, view.bindingOptions[key]

					view.bindings["[data-#{view.bindingDataAttribute}=\"#{key}\"]"] = def
					view.bindings[".#{view.bindingDataAttribute}-#{key}"] = def

			#console.debug 'Enabling stickit.',@bindings
			view.stickit()
