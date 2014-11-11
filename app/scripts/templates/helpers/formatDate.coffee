define [
	'handlebars'
],(Handlebars)->

	formatDate = (source)->
		source.replace /(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1'

	Handlebars.registerHelper 'formatDate', formatDate
	formatDate
