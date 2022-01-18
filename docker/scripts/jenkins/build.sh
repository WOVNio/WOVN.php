#!/usr/bin/env bash
set -eux
export AWS_PROFILE="wovn-code-staging-pipeline"
export AWS_REGION="us-west-2"
export ECR_HOST="257024234524.dkr.ecr.us-west-2.amazonaws.com"
REPO_NAME_WOVNPHP="wovnphp"
REPO_NAME_NGINX="wovnphp-nginx"
CLUSTER_NAME="wovn-library-testing"
TASKDEF_FAMILY_NAME="wovnphp"
ECS_SERVICE_NAME="wovnphp"
PROJECT_DIR=$(dirname "$0")/../../..

commit_hash=$(git rev-parse --short HEAD)
image_tag="${commit_hash}"

sh ${PROJECT_DIR}/build_docker.sh "${REPO_NAME_WOVNPHP}":"${image_tag}"

source ${PROJECT_DIR}/docker/scripts/jenkins/tag_and_push_image.sh

set +x
$(aws ecr get-login --no-include-email --region "${AWS_REGION}" --profile "${AWS_PROFILE}")
set -x

tag_and_push_image "${AWS_REGION}" "${REPO_NAME_WOVNPHP}" "${image_tag}" "staging"

sed -i '' "s#wovnphp:latest#"${REPO_NAME_WOVNPHP}":"${image_tag}"#g" ${PROJECT_DIR}/docker/scripts/jenkins/taskdef.json

cd ${PROJECT_DIR}/docker/scripts/jenkins/
TASKDEF_REVISION=$(aws ecs register-task-definition \
                         --profile "${AWS_PROFILE}" --region "${AWS_REGION}" \
                         --cli-input-json file://$(pwd)/taskdef.json \
                      | jq ."taskDefinition.revision")
echo "${TASKDEF_REVISION}"


echo "Start ECS Rolling deploy. Update ${ECS_SERVICE_NAME} by ${TASKDEF_FAMILY_NAME}:${TASKDEF_REVISION}"
    aws ecs update-service \
      --profile "${AWS_PROFILE}" --region "${AWS_REGION}" \
      --cluster "${CLUSTER_NAME}" \
      --service "${ECS_SERVICE_NAME}" \
      --task-definition "${TASKDEF_FAMILY_NAME}:${TASKDEF_REVISION}"

cd -
