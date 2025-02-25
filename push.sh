#!/bin/sh

if [ $# -eq 0 ]
then
	echo "No push argument was provided"
	exit 1
fi

# push git first
git add .
git commit -m "$1"
git push origin master

# rsync to svn
mkdir ./svn/tags/$2
rsync -ravzp --exclude-from './push.exclude' ./ ./svn/trunk
rsync -ravzp --exclude-from './push.exclude' ./ ./svn/tags/$2
rsync -ravzp ./assets/ ./svn/assets
cd svn
svn cp trunk tags/$2
svn --username shift8 add tags/$2/*
svn --username shift8 ci -m "$1 version $2"
cd ../
