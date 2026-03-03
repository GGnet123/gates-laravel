restart:
	docker compose down && docker compose up -d

build:
	docker compose up -d --build --wait

setup:
	cp -n .env.example .env || true
	docker compose up -d --build --wait app db nginx
	docker compose exec app composer install
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate --seed
	docker compose up -d
create-address:
	docker compose exec app php artisan wallet:create-address eth_sepolia

hot-address:
	docker compose exec app php artisan wallet:hot-address

index:
	docker compose exec app php artisan blockchain:index --base_gate=eth_sepolia
