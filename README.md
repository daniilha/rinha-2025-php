# Rinha 2025 em PHP - Medita��o ZEND????

Seguindo a arquitetura proposta no v�deo do desafio, a solu��o s�o duas APIs rodando php-fpm, ingerindo os payments com APCu e processando com um daemon utilizando postgres, seguindo o princ�pio de que menos � mais. 

S� pra mostrar que PHP ainda vale alguma coisa em webdev hahaha

##  Tecnologias

* **Nginx** - Load balancing
* **PHP-FPM** - Controlador de connection pool
* **PHP8.4** - Engine
* **ACPu** - Armazenamento em mem�ria PHP
* **PDO** - Controlador de conex�o SQL
* **Postgres** - Armazenamento SQL

## Como Rodar


```bash
git clone https://github.com/daniilha/rinha-2025-php
cd rinha-2025-php
docker compose up -d --build
```
