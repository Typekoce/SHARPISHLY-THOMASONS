#!/bin/bash

line='----------'

echo $line"Migrate database tables"
curl http://localhost:8080/php/scaffold/migrate
