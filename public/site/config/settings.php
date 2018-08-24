<?php die(); ?>
{
	"environment": "development",
	"debug": true,
	"datetime": {
		"timezone": "GMT",
		"sdate": "set dynamically",
		"stime": "set dynamically"
	},
	"primrix": {
		"version": "4.0.0",
		"author": "James Dalgarno",
		"contact": "james@imagewebdesign.co.uk"
	},
	"log": true,
	"settings": {
		"ext": ".php",
		"prefix": "{",
		"suffix": "}",
		"development": {
			"default_protocol": "http://",
			"default_www": "",
			"public": "primrix4/public/",
			"site_dir": "primrix4/",
			"rootpath": "/x/htdocs/",
			"logpath": "logs/",
			"domain_id": "primrix4",
			"domain_ext": ".dev",
			"line_end": "\\r\\n"
		},
		"production": {
			"default_protocol": "http://",
			"default_www": "www.",
			"public": "primrix4/public/",
			"site_dir": "primrix4/",
			"rootpath": "/x/htdocs/",
			"logpath": "logs/",
			"domain_id": "primrix",
			"domain_ext": ".com",
			"line_end": "\\n"
		},
		"defaultcontroller": "DefaultController",
		"defaultmethod" : "index"
	},
	"routes": {
		"admin": "admin¦allow1¦allow2"
	},
	"database": {
		"default": "connection1",
		"development": {
			"connection1": {
				"dbtype": "mysql",
				"dbname": "primrix4",
				"dbhost": "localhost",
				"dbuser": "root",
				"dbpass": "password",
				"prefix": "p4_"
			}
		},
		"production": {
			"connection1": {
				"dbtype": "mysql",
				"dbname": "",
				"dbhost": "",
				"dbuser": "",
				"dbpass": "",
				"prefix": "p4_"
			}
		}		
	}
}