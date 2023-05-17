#!/bin/bash

set -ex

PLUGIN_NAME='vk-post-author-display'
PLUGIN_DIR=$(cd $(dirname $(dirname $0)); pwd)
CURRENT_VERSION=$(cat "${PLUGIN_DIR}/post-author-display.php"|grep -i 'version *:'|head -n 1|sed -E 's/^[ *]*Version: *([^ ]*) *$/\1/i')

dist_dir="${PLUGIN_DIR}/dist"
src_dir="${dist_dir}/${PLUGIN_NAME}"
ZIPBALL="${dist_dir}/${PLUGIN_NAME}_v${CURRENT_VERSION}.zip"

[[ -e "${dist_dir}" ]] || mkdir "${dist_dir}"
[[ -e "${ZIPBALL}" ]] && rm -r "${ZIPBALL}"

rsync -av "${PLUGIN_DIR}/" "${src_dir}/" --exclude="dist/" --exclude-from='.distignore'

cd "${dist_dir}/${PLUGIN_NAME}"

exit 0