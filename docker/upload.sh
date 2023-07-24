#!/bin/bash
set -e

TAG=$1
if [ -z "$TAG" ]; then
	echo "USAGE: $0 <tag>"
	exit 1
fi

docker tag \
	nextcloud-certificate24:$TAG \
	registry.cluster.caprino.struktur.de/nextcloud-certificate24:$TAG

docker push \
	registry.cluster.caprino.struktur.de/nextcloud-certificate24:$TAG
