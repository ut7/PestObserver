#!/bin/bash -xe

export R_LIBS_USER=$(dirname "$0")/R-lib

r -e "install.packages('devtools')"

# r -ldevtools -e "install_github('win-stub/x.ent')"
r -ldevtools -e "install_github('ut7/x.ent', ref='bcbd00e')"
