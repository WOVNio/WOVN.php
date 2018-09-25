#!/usr/bin/env bash

FILE_DIR=$(dirname $0)
ROOT_DIR=${FILE_DIR}/..
PHPUNIT_EXE="${ROOT_DIR}/vendor/bin/phpunit"
MD5_EXE='md5sum'; hash ${MD5_EXE} 2> /dev/null || { MD5_EXE="md5"; }
MD5_CMD="tar -cf - ${ROOT_DIR} 2> /dev/null | ${MD5_EXE}"

loop=false
while getopts ":lh" opt
do
  case $opt in
    l)
      loop=true
      ;;
    h)
      echo "Usage: sh $0 [-leh] TEST_SUITE"
      echo "    -l"
      echo "       Run the tests after each change in the root directory."
      echo "    -h"
      echo "       Print this help."
      echo "    TEST_SUITE (integration|unit)"
      echo "       The testsuite to run."
      exit
      ;;
    \?)
      echo "Invalid option: -${OPTARG}"
      sh $0 -h
      exit
      ;;
  esac
done

case ${@:$OPTIND:1} in
  integration)
    phpunit_cmd="${PHPUNIT_EXE} --configuration ${ROOT_DIR}/phpunit_integration.xml"
    ;;
  unit)
    phpunit_cmd=${PHPUNIT_EXE}
    ;;
  *)
    echo "Unknown TEST_SUITE"
    sh $0 -h
    exit
    ;;
esac

eval ${phpunit_cmd}

if [ $loop = true ]
then
  current_md5=`eval ${MD5_CMD}`
  while true
  do
    if [ "${current_md5}" != `eval ${MD5_CMD}` ]
    then
      eval ${phpunit_cmd}
      current_md5=`eval ${MD5_CMD}`
    fi
  done
fi

