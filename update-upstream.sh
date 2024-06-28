#!/usr/bin/env bash

# This script is used to update the upstream branch with the latest changes from the Apache Avro repository.

SCRIPT_DIR=$(realpath -e -- "$(dirname -- "${BASH_SOURCE[0]}")");
cd "${SCRIPT_DIR:?}" || exit 1

fail() {
    printf "$@" 1>&2
    echo 1>&2
    exit 1
}

# If there are any changes in the working directory, the script will abort.
if ! git diff --quiet; then
    fail "There are changes in the working directory. Please commit or stash them before running this script."
fi

current_ref=$(git symbolic-ref --short HEAD) || fail "Failed to get the current branch. Please make sure you are on a branch."

git switch upstream || exit 1

rm -rf "${SCRIPT_DIR:?}"/lib "${SCRIPT_DIR:?}"/test

mkdir -p "${SCRIPT_DIR:?}"/apache && cd "${SCRIPT_DIR:?}"/apache || exit 1
curl https://github.com/apache/avro/archive/refs/heads/main.tar.gz --location \
    | tar --extract --gunzip --file - --strip-components=3 avro-main/lang/php/lib avro-main/lang/php/test || exit 1

git add lib test || exit 1
git commit --message="Update upstream branch"
git switch "${current_ref:?}" || exit 1