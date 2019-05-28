.PHONY: help bash install start stop test update

help: ## Show this help.
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'

bash: ## Run the docker container and connect to it using bash.
	docker-compose run php bash

install: ## Installs the dependencies of the project without updating any of them.
	docker-compose run php install

start: ## Starts the local server running the project.
	docker-compose up -d

stop: ## Stops the local server running the project.
	docker-compose stop

test: ## Test the project.
	docker-compose run php composer test

update: ## Update the dependencies.
	docker-compose run php composer update
