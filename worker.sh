#!/bin/bash

clear

line='-------------------------'

echo $line"Start background worker"

docker exec -it sharpishly-php php web/php/src/worker.php
