# ?? Rinha de Backend 2025 - Rust

Este reposit�rio cont�m a minha participa��o na **Rinha de Backend 2025**, implementada em **Rust**.

## ?? Tecnologias Utilizadas

- [Rust](https://www.rust-lang.org/)
- [Axum 0.8](https://docs.rs/axum) ? Web framework moderno, baseado em Tokio
- [Tokio 1.46](https://tokio.rs/) ? Runtime ass�ncrono de alta performance
- [Serde](https://serde.rs/) ? Serializa��o/deserializa��o eficiente de JSON
- [Reqwest](https://docs.rs/reqwest) ? Cliente HTTP ass�ncrono
- [Redis 0.32](https://docs.rs/redis) ? Gerenciamento de cache, fila de transa��es, etc
- [Chrono](https://docs.rs/chrono) ? Manipula��o de datas e hor�rios

## ?? Como rodar

Certifique-se de ter o **Docker** e o **Docker Compose** instalados.

```bash
git clone https://github.com/andersongomes001/rinha-2025.git
cd rinha-2025
docker compose up --build
```
