# 🐳 Projeto WordPress Dockerizado

Ambiente WordPress completo com Docker + MySQL + phpMyAdmin.

---

## 🚀 Instruções rápidas

### 1. Pré-requisitos

- Docker instalado ([baixe aqui](https://www.docker.com/products/docker-desktop))
- Git instalado

### 2. Clone o repositório

```bash
git clone https://github.com/seu-usuario/seu-projeto.git
cd seu-projeto
````
### 3. Configure variáveis de ambiente

- criar arquivo .env
``` bash
WORDPRESS_DB_NAME=wordpress
WORDPRESS_DB_USER=wordpress
WORDPRESS_DB_PASSWORD=wordpress
MYSQL_ROOT_PASSWORD=rootpass
```

### 4. Suba os containers
```bash
docker-compose up -d --build
```

### 5. 🔎 Acesse o projeto
Serviço	Endereço
- WordPress	http://localhost:80
- phpMyAdmin	http://localhost:8081

