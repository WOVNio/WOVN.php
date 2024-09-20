#!/usr/bin/env bash

set -x

CURRENT_DIR=$(cd $(dirname $0); pwd)
DOCKER_IMAGE=${1:-php:8.0-apache-buster}
TEST_DIR="${CURRENT_DIR}/../test"

if [[ "${DOCKER_IMAGE}" =~ ^.*php:?(7\.[1-9]|8\.[0-9]).*$ ]]; then
  find ${TEST_DIR} -type f -name '*.php' -print0 | xargs -0 sed -i -r 's/function setUp\(\)(: void)?/function setUp(): void/g'
  find ${TEST_DIR} -type f -name "*.php" -print0 | xargs -0 sed -i -r 's/function setUpBeforeClass\(\)(: void)?/function setUpBeforeClass(): void/g'
  find ${TEST_DIR} -type f -name "*.php" -print0 | xargs -0 sed -i -r 's/function tearDown\(\)(: void)?/function tearDown(): void/g'
  find ${TEST_DIR} -type f -name "*.php" -print0 | xargs -0 sed -i -r 's/function tearDownAfterClass\(\)(: void)?/function tearDownAfterClass(): void/g'
fi

if [[ "${DOCKER_IMAGE}" =~ ^.*php:?5\.[0-9].*$ ]]; then
  find ${TEST_DIR} -type f -name '*.php' -print0 | xargs -0 sed -i -r 's/^use PHPUnit\\\Framework\\\TestCase;$//g'
  find ${TEST_DIR} -type f -name '*.php' -print0 | xargs -0 sed -i -r 's/extends TestCase(.*$)/extends \\\PHPUnit_Framework_TestCase\1/g'
fi
