# syntax=docker/dockerfile:1.7

# ---------------------------------------------------------------------------
# Dependencias Node
# ---------------------------------------------------------------------------
FROM node:22.23.5-alpine3.24 AS dependencies

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# ---------------------------------------------------------------------------
# Desarrollo: ng serve con hot reload (target usado por docker-compose)
# ---------------------------------------------------------------------------
FROM dependencies AS dev

ARG UID=1000
ARG GID=1000

COPY . .
RUN chown -R "${UID}:${GID}" /app

USER ${UID}:${GID}

ENV HOME=/tmp
ENV NG_CLI_ANALYTICS=false

EXPOSE 4200

CMD ["npm", "start", "--", "--host", "0.0.0.0", "--port", "4200", "--poll", "1000"]

# ---------------------------------------------------------------------------
# Build de producción
# ---------------------------------------------------------------------------
FROM dependencies AS build

COPY . .
RUN npm run build -- --configuration production

# ---------------------------------------------------------------------------
# Producción: Nginx sirviendo los estáticos de Angular
# ---------------------------------------------------------------------------
FROM nginx:1.30.7-alpine3.24-slim AS prod

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist/talitacumi/browser /usr/share/nginx/html

EXPOSE 80
