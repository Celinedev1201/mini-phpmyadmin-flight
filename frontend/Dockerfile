# Image Node.js légère pour le frontend
FROM node:24-alpine

# Dossier principal du frontend dans le conteneur
WORKDIR /app

# Copie d’abord les fichiers qui décrivent les dépendances
COPY package*.json ./

# Installe exactement les versions enregistrées dans package-lock.json
RUN npm ci

# Copie le reste du code React
COPY . .

# Port utilisé par le serveur Vite
EXPOSE 5173

# Lance Vite et autorise l’accès depuis l’extérieur du conteneur
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]