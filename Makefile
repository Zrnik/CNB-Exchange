permissions:
	sudo chown -R 1000:1000 .

composer-update:
	docker run -t -v $(shell pwd):/var/www/html datixo/php-8.3-fpm composer update
	make permissions

composer-install:
	docker run -t -v $(shell pwd):/var/www/html datixo/php-8.3-fpm composer install
	make permissions

phpstan:
	docker run -t -v $(shell pwd):/var/www/html datixo/php-8.3-fpm php vendor/bin/phpstan
	make permissions

phpunit:
	docker run -t -v $(shell pwd):/var/www/html datixo/php-8.3-fpm php vendor/bin/phpunit
	make permissions