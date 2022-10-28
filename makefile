composer-update:
	docker run --volume $(PWD):/app composer:2.3 update --ignore-platform-reqs

phpstan:
	docker run --volume $(PWD):/var/www/html --workdir /var/www/html php:7.4 php ./vendor/bin/phpstan

phpunit:
	docker run --volume $(PWD):/var/www/html --workdir /var/www/html php:7.4 php ./vendor/bin/phpunit
