# Personal Accountant Docker

Official production-ready image - [Docker Hub](https://hub.docker.com/r/vchychuzhko/personal-accountant).

## Compose

[docker-compose.prod.yml](../docker-compose.prod.yml) contains a ready-to-use configuration, that can be used in, for example, Portainer Stack.

Pay attention to default values:
- Database credentials: `app:app`
- Port: `8996`

## Build

### Build image:

```bash
docker build --network=host -f .docker/php/Dockerfile -t vchychuzhko/personal-accountant:1.0 .
```

Use `--network=host` flag for ufw compatibility.

### Push image:

```bash
docker push vchychuzhko/personal-accountant:1.0
```

Optionally push "latest" image:

```bash
docker push vchychuzhko/personal-accountant:latest
```
