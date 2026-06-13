#!/usr/bin/env python3
"""macOS app launcher: runs sync loop directly using existing local config."""

from sync_client import cmd_run, get_paths, log


def main() -> int:
    paths = get_paths()
    log("Starting PLUSSCI sync app mode...")
    return cmd_run(paths)


if __name__ == "__main__":
    raise SystemExit(main())
