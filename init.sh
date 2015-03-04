#!/bin/sh

# Init repo
git submodule init;
git submodule update;

# Setup Config
cp -n .config/.config.php config.php;
git update-index --assume-unchanged config.php;

# Build
php .script/build.php;

# Test
php .script/test.php;