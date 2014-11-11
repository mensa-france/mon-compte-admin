define [
	'handlebars'
],(Handlebars)->

	formatDate = (source)->
		if (source)
			source.replace /(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1'
		else
			source

	Handlebars.registerHelper 'formatDate', formatDate
	formatDate
