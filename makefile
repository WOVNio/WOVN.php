DOCKER_COMPOSE_YML = docker/apache.yml
.PHONY: build stop start clean dev_setup test test_debug

build:
	docker-compose -f $(DOCKER_COMPOSE_YML) build

stop:
	docker-compose -f $(DOCKER_COMPOSE_YML) rm -sf

start:
	docker-compose -f $(DOCKER_COMPOSE_YML) up

clean:
	docker-compose -f $(DOCKER_COMPOSE_YML) down --rmi all --volumes

dev_setup:
	composer install

test:
	vendor/bin/phpunit

test_debug:
	vendor/bin/phpunit --debug


