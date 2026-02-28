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

# Only install deps if node_modules is missing or package.json changed, then start dev server
CMD ["sh", "-c", "if [ ! -d node_modules/.package-lock.json ] || [ package.json -nt node_modules/.package-lock.json ]; then npm install; fi && npm run dev -- --host 0.0.0.0"]
