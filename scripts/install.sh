#!/bin/bash

# machine controller
CTL="${BASEURL}index.php?/module/machine/"

# Get the scripts in the proper directories
"${CURL[@]}" "${CTL}get_script/reportcommon.py" -o "${MUNKIPATH}munkilib/reportcommon.py"

# Make the symlink to the current Python version
/bin/rm -rf "${MUNKIPATH}munkireport-python3"; /bin/ln -s "/Library/ManagedFrameworks/Python/Python3.framework/Versions/Current/Resources/Python.app/Contents/MacOS/Python" "${MUNKIPATH}munkireport-python3"
