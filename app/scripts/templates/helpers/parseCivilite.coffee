define [
    'handlebars'
    'version'
],(Handlebars, version)->

    Handlebars.registerHelper 'parseCivilite', (source)->
    	switch source
    		when 'mister' then 'M.'
    		when 'ms' then 'Mlle'
    		when 'mrs' then 'Mme'
    		else '_'
