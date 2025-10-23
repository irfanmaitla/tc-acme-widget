# Acme Widget Co - Shopping Basket

A PHP implementation of a shopping basket system with product catalogue, delivery rules, and promotional offers.

## Features

- **Product Catalogue**: Manages available products
- **Delivery Rules**: Tiered delivery costs based on order total
- **Promotional Offers**: Buy one get one half price functionality
- **Dependency Injection**: All dependencies are injected for testability
- **Strategy Pattern**: Flexible delivery rules and offers
- **Type Safety**: Strict types throughout
- **Full Test Coverage**: Unit and integration tests with PHPUnit
- **Static Analysis**: PHPStan level 8 compliance
- **Docker Support**: Easy setup with Docker and Docker Compose

## Quick Start

### Local Installation

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse

# Run example
php example.php
```

### Using Docker

```bash
# Build and start
docker-compose up -d --build

# Install dependencies
docker-compose exec app composer install

# Run tests
docker-compose exec app composer test

# Run example
docker-compose exec app php example.php
```

## Test Results

All four examples from the assignment pass:

- **B01, G01** → $37.85 ✓
- **R01, R01** → $54.37 ✓
- **R01, G01** → $60.85 ✓
- **B01, B01, R01, R01, R01** → $98.27 ✓

## Architecture

- Dependency Injection
- Strategy Pattern for delivery rules and offers
- Interface Segregation
- Strict type declarations
- PHPStan level 8 compliance

## License

This is a coding challenge project for demonstration purposes.
