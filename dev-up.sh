#!/bin/bash

line='---------------'

echo $line"Build project starting containers"

docker compose down --remove-orphans

docker compose up -d --build

docker compose logs -f nginx
