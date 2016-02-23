#!/bin/bash -ex

(
 echo ==============================================
 make --debug=b sql 2>&1|awk '{ print strftime("%Y-%m-%d %H:%M:%S"), $0; fflush(); }'
) >> indexation.log
