DOCKER_COMPOSE_YML = docker/apache.yml
# DOCKER_COMPOSE_YML = docker/nginx.yml
# DOCKER_COMPOSE_YML = docker/wp_apache.yml
DOCKER_IMAGE = php:7.4-apache

.PHONY: build stop start clean dev_setup test test_debug lint_with_docker test_unit_with_docker test_integration_with_docker test_with_circleci

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
	make convert
	vendor/bin/phpunit
	make revert

convert:
	./scripts/convert.sh php:8.0-apache-buster

revert:
	./scripts/revert.sh php:8.0-apache-buster

start_test:
	env DOCKER_IMAGE=${DOCKER_IMAGE} docker-compose -f docker/test.yml up

lint:
	vendor/bin/phpcs src
	vendor/bin/phpcs test

lint_with_docker:
	docker exec -it -w /opt/project apache /bin/bash -c "vendor/bin/phpcs"

# Run `start_test` before
test_unit_with_docker:
	./scripts/run_phpunit_tests.sh ${DOCKER_IMAGE}

# Run `start_test` before
test_integration_with_docker:
	./scripts/run_integration_tests.sh ${DOCKER_IMAGE}

test_with_circleci:
	circleci local execute --job $(CIRCLECI_JOB)
