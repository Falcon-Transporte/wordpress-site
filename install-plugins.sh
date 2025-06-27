#!/bin/bash
set -e

# Plugins a instalar
wp plugin install advanced-access-manager --activate
wp plugin install colorlib-login-customizer --activate
wp plugin install import-users-from-csv-with-meta --activate
wp plugin install media-cleaner --activate
wp plugin install wp-optimize --activate
wp plugin install wp-smushit --activate
wp plugin install wp-super-cache --activate

chmod +x install-plugins.sh