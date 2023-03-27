#!/usr/bin/env sh

set -x
set -e

projectRoot="${PWD}"
totalExitCode=0
for subDir in $(find src -maxdepth 4 -type f -name phpunit.xml.dist -printf '%h\n' | sort)
do
    echo "::group::${subDir}"
    echo "${projectRoot}/${subDir}"
    cd "${projectRoot}/${subDir}"

    subExitCode=0
    ./vendor/bin/phpunit || subExitCode=1
    totalExitCode=$(( subExitCode || totalExitCode ))
    echo '::endgroup::'

    if [ $subExitCode -ne 0 ]; then
        echo "::error::${subDir} error"
    fi
done

exit $totalExitCode
