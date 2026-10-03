# Personal Accountant Docker

Official production-ready image - [Docker Hub](https://hub.docker.com/r/vchychuzhko/personal-accountant).

## Compose

[docker-compose.prod.yml](../docker-compose.prod.yml) contains a ready-to-use configuration that can be used in, for example, Portainer or Dockhand Stack.

Pay attention to default values:
- Database credentials: `app:app`
- Port: `8996`
- Image: if deployed to an ARM architecture system (like Rasperry Pi), use `:arm` tag

## Build and Push

```bash
docker build --network=host -f .docker/php/Dockerfile -t vchychuzhko/personal-accountant:latest --push .
```

Use `--network=host` flag for ufw compatibility.

### ARM version

To use the ARM version, build and deploy it from an ARM architecture system (like Rasperry Pi):

```bash
docker build -f .docker/php/Dockerfile -t vchychuzhko/personal-accountant:arm --push .
```
