DOCKER_COMPOSE_YML = docker/apache.yml
# DOCKER_COMPOSE_YML = docker/nginx.yml
# DOCKER_COMPOSE_YML = docker/wp_apache.yml
DOCKER_IMAGE = php:7.3-apache

.PHONY: build
build:
	docker-compose -f $(DOCKER_COMPOSE_YML) build --build-arg DOCKER_IMAGE=${DOCKER_IMAGE}

.PHONY: stop
stop:
	docker-compose -f $(DOCKER_COMPOSE_YML) rm -sf

.PHONY: start
start:
	docker-compose -f $(DOCKER_COMPOSE_YML) up

.PHONY: clean
clean:
	docker-compose -f $(DOCKER_COMPOSE_YML) down --rmi all --volumes

.PHONY: dev_setup
dev_setup:
	composer install

.PHONY: test
test:
	make convert
	make phpunit
	make revert

.PHONY: phpunit
phpunit:
	vendor/bin/phpunit

# `convert` and `revert` are used to support difference of PHP syntax in test files.
.PHONY: convert
convert:
	./scripts/convert.sh

.PHONY: revert
revert:
	./scripts/revert.sh

.PHONY: start_test
start_test:
	env DOCKER_IMAGE=${DOCKER_IMAGE} docker-compose -f docker/test.yml up

.PHONY: lint
lint:
	vendor/bin/phpcs src
	vendor/bin/phpcs test

.PHONY: lint_with_docker
lint_with_docker:
	docker exec -it -w /opt/project apache /bin/bash -c "vendor/bin/phpcs"

# Run `start_test` before
.PHONY: test_unit_with_docker
test_unit_with_docker:
	./scripts/run_phpunit_tests.sh ${DOCKER_IMAGE}

# Run `start_test` before
.PHONY: test_integration_with_docker
test_integration_with_docker:
	./scripts/run_integration_tests.sh ${DOCKER_IMAGE}

.PHONY: test_with_circleci
test_with_circleci:
	circleci local execute --job $(CIRCLECI_JOB)
