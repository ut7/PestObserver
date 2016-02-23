#!/bin/bash -ex

make -j 12 -i sql 2>&1|awk '{ print strftime("%Y-%m-%d %H:%M:%S"), $0; fflush(); }' >> indexation.log 2>&1
