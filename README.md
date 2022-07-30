# How to use this project

## Clone repository

1. Clone it from GitHub.
2. Create a new local repository and copy the files from this repo into it.

``` shell
git clone https://github.com/AndriyMand/BtcTestApplication.git
```

## Run application in a Docker

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/)
2. Run `docker compose build --pull --no-cache` to build fresh images
3. Run `docker compose up` (the logs will be displayed in the current shell)
4. Open `https://localhost` in your favorite web browser and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
5. Run `docker compose down --remove-orphans` to stop the Docker containers.

## Usefull links

1. BTC API - https://localhost/api/doc
2. Main page - https://localhost/

Created by Andriy Mandybur
