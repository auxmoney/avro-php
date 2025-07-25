# Development Container Setup

This folder contains the VS Code Dev Container configuration for the Avro PHP project.

## What's Included

- **PHP 8.3** development environment with Xdebug
- **Composer** for dependency management
- **PHPUnit** for testing
- **Node.js** for test generation tools
- **Git** and **GitHub CLI** for version control

## Usage

### VS Code Dev Container

1. Install the [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers) in VS Code
2. Open the project in VS Code
3. When prompted, click "Reopen in Container" or use `Ctrl+Shift+P` → "Dev Containers: Reopen in Container"

### Manual Docker Usage

You can also use the containers manually:

```bash
# Run the main development container
docker compose -f .devcontainer/docker-compose.yaml run --rm dev

# Run the test generator
docker compose -f .devcontainer/docker-compose.yaml run --rm test-generator
```

## Services

### `dev` Service
- **Image**: `webdevops/php-dev:8.3`
- **Purpose**: Main development environment
- **Features**: PHP 8.3, Composer, Xdebug, PHPUnit
- **Working Directory**: `/avro-php`

### `test-generator` Service
- **Image**: `node:latest`
- **Purpose**: Test case generation
- **Features**: Node.js, npm
- **Working Directory**: `/avro-php`

## VS Code Extensions

The following extensions are automatically installed in the container:

- **Intelephense** - PHP language server
- **PHP Debug** - Xdebug integration
- **PHPUnit** - Test runner integration
- **PHP Namespace Resolver** - Namespace management
- **PHP CS Fixer** - Code formatting
- **JSON** - JSON support

## Environment Variables

The following environment variables can be customized:

- `DOCKER_HOME` - Home directory path (defaults to `$HOME`)
- `DOCKER_USER` - User to run as (defaults to `$USER`)

## Post-Create Commands

When the container is created, the following commands are automatically run:

1. `composer install` - Install PHP dependencies

## Troubleshooting

### Permission Issues
If you encounter permission issues, ensure your user ID matches the container user:

```bash
# Check your user ID
id -u

# The container should run as the same user ID
```

### Xdebug Issues
If Xdebug isn't working:

1. Ensure the `xdebug.mode=debug` is set in your PHP configuration
2. Check that port 9003 is available for Xdebug connections
3. Verify your IDE is configured to listen on the correct port 