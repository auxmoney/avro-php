#!/usr/bin/env bash

# This script is used to update the upstream branch with the latest changes from the Apache Avro repository.

SCRIPT_DIR=$(realpath -e -- "$(dirname -- "${BASH_SOURCE[0]}")");
cd "${SCRIPT_DIR:?}" || exit 1

fail() {
    printf "$@" 1>&2
    echo 1>&2
    exit 1
}

git-filter-repo --version &>/dev/null || fail "Please install git-filter-repo before running this script."

# If there are any changes in the working directory, the script will abort.
if ! git diff --quiet; then
    fail "There are changes in the working directory. Please commit or stash them before running this script."
fi

current_ref=$(git symbolic-ref --short HEAD) || fail "Failed to get the current branch. Please make sure you are on a branch."

git switch upstream || fail "Failed to switch to the upstream branch."
git remote get-url upstream &>/dev/null || git remote add upstream git@github.com:apache/avro.git
git fetch upstream || fail "Failed to fetch the upstream branch."
git reset --hard upstream/main || fail "Failed to reset the upstream branch."
git filter-repo \
        --refs upstream \
        --path lang/php/lib \
        --path lang/php/test \
        --path-rename lang/php/lib:apache/lib \
        --path-rename lang/php/test:apache/test \
        --force \
    || exit 1

git switch "${current_ref:?}" || exit 1