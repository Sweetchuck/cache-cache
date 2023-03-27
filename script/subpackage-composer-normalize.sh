#!/usr/bin/env sh

set -x
set -e

totalExitCode=0
for pathToComposerJson in $(find src -maxdepth 4 -type f -name composer.json | sort)
do
    echo "::group::${pathToComposerJson}"

    subExitCode=0
    composer normalize "${pathToComposerJson}" ${@} || subExitCode=1
    totalExitCode=$(( subExitCode || totalExitCode ))
    echo '::endgroup::'

    if [ $subExitCode -ne 0 ]; then
        echo "::error::${pathToComposerJson} error"
    fi
done

exit $totalExitCode
