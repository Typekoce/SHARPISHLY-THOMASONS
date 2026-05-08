#!/bin/bash

clear

line='-----'

echo $line"Git add"
git add .

echo $line"Git commit"
git commit -m "feat(install.sh):Conversion of Makefile to install.sh"

echo $line"Git push"
git push