FROM node:20-bullseye-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends git build-essential python3 make g++ ca-certificates \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Install dependencies based on package-lock.json
COPY package*.json ./
RUN npm ci --no-audit --prefer-offline

COPY . .

EXPOSE 5173

# Always install/update deps on container start so that sync+restart from
# docker compose watch picks up package.json changes without a full rebuild.
CMD ["sh", "-c", "npm install && npm run dev -- --host 0.0.0.0"]
