#!/bin/bash
# Author: The Portland Company @theportlandco, Spencer Hill @s3w47m88
# License: GPL v3
# Description: This script automatically deploys your, Git managed, WordPress Plugin to the WordPress Plugin Directory without requiring you to deal with all the extra SVN crap. The only 
# Credit: Though this script is heavily modified we want to thank

# Configurable Variables
wordpress_username="d363f86b" # This is the only line you need to update. Insert your WordPress.org username.

# Current directory
plugin_directory=${PWD##*/}
svn_ignored_files="deploy-to-wordpress.sh
	README.md
	readme.md
	convert-readme.sh
	.git
	.gitignore
	.gitmodules
	.gitcommitlog
	submodule
	assets/images/plugin-directory-assets
	deployment-scripts"
temporary_path_of_svn_repository="/tmp/$plugin_directory"
svn_repository_url="http://plugins.svn.wordpress.org/$plugin_directory"


clear

echo "Before we begin 'checking in' the files to the WordPress.org Plugins Directory via SVN this script will Git add, commit and push the latest changes."
echo "Also, please note that the following files will be ignored:" $svn_ignored_files
echo 
echo "Executing 'git add -A' now."
git add -A
echo
echo "- Enter an Commit Message that will be used by Git and SVN:"
read commit_message
echo 
echo "Executing git commit -m now."
git commit -m "$commit_message"
echo 
echo "Executing 'git push'."
git push
echo


echo "- Creating a local, temporary, copy (SVN Checkout) of your SVN repository at $temporary_path_of_svn_repository."
svn co $svn_repository_url $temporary_path_of_svn_repository
echo


echo "- Copying the Git Master Branch, but without the .git files, from ./ to the, previously Cloned, SVN Repository to $temporary_path_of_svn_repository/trunk."
#git checkout-index -a -f --prefix=$temporary_path_of_svn_repository/trunk/
cp -R ./* $temporary_path_of_svn_repository/trunk/
echo


echo "- Added files to be ignored to the SVN ignore list."
#svn propset svn:ignore "$svn_ignored_files" $temporary_path_of_svn_repository/trunk/
svn propset svn:ignore ".gitcommitlog" $temporary_path_of_svn_repository/trunk/
echo


echo "- Moving the Plugin Directory specific assets to the correct location."
cp -r assets/images/plugin-directory-assets/ $temporary_path_of_svn_repository/assets
echo


echo "- Converting the Mark Down file to WordPress', dumbed-down, .TXT file."
bash deployment-scripts/convert-readme.sh readme.md readme.txt to-wp
mv readme.txt $temporary_path_of_svn_repository/trunk/readme.txt
echo

echo "- Moving into $temporary_path_of_svn_repository."
cd $temporary_path_of_svn_repository/

echo "rm-ing files from ignore list because, upon initial commits, sometimes these files don't get removed if they're not ignored first."
svn delete $temporary_path_of_svn_repository/trunk/deployment-scripts
svn delete $temporary_path_of_svn_repository/trunk/assets/images/plugin-directory-assets
svn delete $temporary_path_of_svn_repository/trunk/submodule
svn delete $temporary_path_of_svn_repository/trunk/README.md
svn delete $temporary_path_of_svn_repository/trunk/readme.md
svn delete $temporary_path_of_svn_repository/trunk/.git
svn delete $temporary_path_of_svn_repository/trunk/.gitignore
svn delete $temporary_path_of_svn_repository/trunk/.gitmodules
svn delete $temporary_path_of_svn_repository/trunk/.gitcommitlog

#svn add --force * --auto-props --parents --depth infinity -q
echo


echo "- Committing your changes to WordPress.org so they will appear on your Plugin page."
svn commit --username=$wordpress_username -m "$commit_message"
echo


echo "- Removing the SVN repository from $temporary_path_of_svn_repository"
rm -fr $temporary_path_of_svn_repository/

echo
echo "Your Plugin has been successfully committed to the WordPress Plugins repository."
echo "Though it may take a few minutes to propagate."