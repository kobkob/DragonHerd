.PHONY: help install test phpcs phpstan coverage clean build package
.DEFAULT_GOAL := help

# Colors for output
YELLOW := \033[33m
GREEN := \033[32m
RED := \033[31m
RESET := \033[0m

help: ## Show this help message
	@echo "$(YELLOW)DragonHerd WordPress Plugin - Development Commands$(RESET)\n"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-20s$(RESET) %s\n", $$1, $$2}'

install: ## Install dependencies
	@echo "$(YELLOW)Installing Composer dependencies...$(RESET)"
	composer install

test: ## Run PHPUnit tests
	@echo "$(YELLOW)Running PHPUnit tests...$(RESET)"
	composer run test

phpcs: ## Run PHP CodeSniffer
	@echo "$(YELLOW)Running PHP CodeSniffer...$(RESET)"
	composer run phpcs

phpcs-fix: ## Fix PHP CodeSniffer issues
	@echo "$(YELLOW)Fixing PHP CodeSniffer issues...$(RESET)"
	composer run phpcs:fix

phpstan: ## Run PHPStan static analysis
	@echo "$(YELLOW)Running PHPStan...$(RESET)"
	composer run phpstan

coverage: ## Generate test coverage report
	@echo "$(YELLOW)Generating coverage report...$(RESET)"
	composer run test:coverage
	@echo "$(GREEN)Coverage report generated in coverage/html/index.html$(RESET)"

ci: ## Run all CI checks (phpcs, phpstan, tests)
	@echo "$(YELLOW)Running full CI pipeline...$(RESET)"
	composer run ci

clean: ## Clean build artifacts and caches
	@echo "$(YELLOW)Cleaning build artifacts...$(RESET)"
	rm -rf coverage/ build/ dist/ .phpunit.cache/
	@echo "$(GREEN)Clean complete$(RESET)"

build: clean ## Build plugin for distribution
	@echo "$(YELLOW)Building plugin package...$(RESET)"
	mkdir -p build
	rsync -av --exclude-from='.distignore' . build/dragonherd/
	@echo "$(GREEN)Plugin built in build/dragonherd/$(RESET)"

package: build ## Create distributable zip package
	@echo "$(YELLOW)Creating package...$(RESET)"
	cd build && zip -r dragonherd.zip dragonherd/
	@echo "$(GREEN)Package created: build/dragonherd.zip$(RESET)"

dev-setup: install ## Set up development environment
	@echo "$(YELLOW)Setting up development environment...$(RESET)"
	@echo "$(GREEN)Development environment ready!$(RESET)"
	@echo "$(YELLOW)Available commands:$(RESET)"
	@$(MAKE) help

validate: ci ## Validate code (alias for ci)
	@echo "$(GREEN)Code validation complete!$(RESET)"

security: ## Run security audit
	@echo "$(YELLOW)Running security audit...$(RESET)"
	composer audit
