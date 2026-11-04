#!/bin/sh
cd "$(dirname "$0")" || exit 1
exec php -S 127.0.0.1:8765
