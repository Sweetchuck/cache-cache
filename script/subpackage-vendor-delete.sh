#!/usr/bin/env bash

find src -maxdepth 4 -type d -name 'vendor'
rm \
    --recursive \
    --force \
    $(find src -maxdepth 4 -type d -name 'vendor')
