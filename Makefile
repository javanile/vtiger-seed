

up:
	docker-compose up -d

test: up
	docker-compose exec vtiger php vtiger-seed.php demo.csv
