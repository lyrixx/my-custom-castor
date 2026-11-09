# My custom Castor

The static binary of [Castor](https://github.com/jolicode/castor), with `pdo_sqlite` added to the
official PHP extensions. Linux x86_64 only.

## Install

```bash
curl -fsSL -o /usr/local/bin/castor https://github.com/lyrixx/my-custom-castor/releases/latest/download/castor.linux-amd64
chmod +x /usr/local/bin/castor
```

## How it is built

Every day, GitHub Actions compiles the latest official release of Castor, unless it is already
published here (`castor binary:publish`). Each release has the same tag as the Castor version it is
built from.

To build a version locally, in `build/`: `castor binary:build v1.9.1`.
