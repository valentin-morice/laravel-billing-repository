.PHONY: help install test format analyse shell clean

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install dependencies
	@docker run --rm -v .:/app -w /app composer:2 sh -c 'git config --global --add safe.directory /app && composer install'

test: ## Run tests
	@docker run --rm -v .:/app -w /app composer:2 sh -c 'git config --global --add safe.directory /app && composer test'

format: ## Format code
	@docker run --rm -v .:/app -w /app composer:2 sh -c 'git config --global --add safe.directory /app && composer format'

analyse: ## Run static analysis
	@docker run --rm -v .:/app -w /app composer:2 sh -c 'git config --global --add safe.directory /app && composer analyse'

shell: ## Open a shell in the container
	@docker run --rm -it -v .:/app -w /app composer:2 sh -c 'git config --global --add safe.directory /app && sh'

clean: ## Remove vendor directory and composer.lock
	rm -rf vendor composer.lock
