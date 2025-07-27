# Rinha 2025 em PHP - Meditação ZEND????

Seguindo a arquitetura proposta no vídeo do desafio, a solução são duas APIs rodando php-fpm, ingerindo os payments com APCu e processando com um daemon utilizando postgres, seguindo o princípio de que menos é mais. 

Só pra mostrar que PHP ainda vale alguma coisa em webdev hahaha

##  Tecnologias

* **Nginx** - Load balancing
* **PHP-FPM** - Controlador de connection pool
* **PHP8.4** - Engine
* **ACPu** - Armazenamento em memória PHP
* **PDO** - Controlador de conexão SQL
* **Postgres** - Armazenamento SQL

## Como Rodar


```bash
git clone https://github.com/daniilha/rinha-2025-php
cd rinha-2025-php
docker compose up -d --build
```
