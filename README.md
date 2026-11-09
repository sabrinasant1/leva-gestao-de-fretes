# leva – Gestão de Fretes (PHP)
Requisitos: PHP 8+ com extensões pdo_sqlite, mbstring e iconv.
Executar: `cd leva && php -S localhost:8000` e abrir http://localhost:8000
O banco SQLite é criado sozinho em data/leva.sqlite.
Ordem de uso: cadastre Transportador, Produtos e dois Endereços (origem e destino) -> Novo frete -> Relatórios (CSV/PDF).
