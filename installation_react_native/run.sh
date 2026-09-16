#!/bin/bash

line='---------------------'

echo $line"Run container"
docker compose run --service-ports react-native

# Initialize a new Expo app if starting fresh
npx create-expo-app@latest sharpishly-mobile
cd sharpishly-mobile

# Start Expo on LAN mode
npx expo start --host lan