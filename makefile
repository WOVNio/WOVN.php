# DOCKER_COMPOSE_YML = docker/apache.yml
DOCKER_COMPOSE_YML = docker/test.yml
DOCKER_IMAGE = php:7.3-apache

.PHONY: build stop start clean dev_setup test test_debug test_unit_with_docker test_integration_with_docker

build:
	docker-compose -f $(DOCKER_COMPOSE_YML) build

stop:
	docker-compose -f $(DOCKER_COMPOSE_YML) rm -sf

start:
	env DOCKER_IMAGE=${DOCKER_IMAGE} docker-compose -f $(DOCKER_COMPOSE_YML) up

clean:
	docker-compose -f $(DOCKER_COMPOSE_YML) down --rmi all --volumes

dev_setup:
	composer install

test:
	vendor/bin/phpunit

# Before using these commands, make sure that dokcer is running with test.yml.
test_unit_with_docker:
	docker exec -it -w /opt/project apache /bin/bash -c "vendor/bin/phpunit"

test_integration_with_docker:
	docker exec -it -w /opt/project apache /bin/bash -c "vendor/bin/phpunit --configuration phpunit_integration.xml"
