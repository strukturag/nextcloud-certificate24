#!/bin/bash
set -e

TAG=$1
if [ -z "$TAG" ]; then
	echo "USAGE: $0 <tag>"
	exit 1
fi

docker build \
	--rm \
	--pull \
	--progress plain \
	--tag nextcloud-certificate24:$TAG \
	-f docker/Dockerfile \
	.
